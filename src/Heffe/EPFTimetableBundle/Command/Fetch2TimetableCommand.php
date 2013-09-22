<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Fabien
 * Date: 23/02/13
 * Time: 14:45
 * To change this template use File | Settings | File Templates.
 */
namespace Heffe\EPFTimetableBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Heffe\EPFTimetableBundle\Entity\Event as hEvent;
use Heffe\EPFTimetableBundle\Entity\User;

class Fetch2TimetableCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('heffe:timetable:update2')
            ->setDescription('Imports the EPF timetable data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * Fetching client and google services
         */
        $client = $this->getContainer()->get('heffe_epf_timetable.httpclient');
        $googleService = $this->getContainer()->get('heffe_epf_timetable.googleservice');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $userRepo = $em->getRepository('HeffeEPFTimetableBundle:User');
        $users = $userRepo->findValidUsers();

        // Fetching logging service
        $logger = $this->getContainer()->get('heffe_epf_timetable.logging')->logger;


        /**
         * Putting all subjects from the DB into an array
         * Basically:
         * $subjects[ "MSMSI5SE02" ] = "Droit TIC"
         */
        $dbSubjects = $em->getRepository('HeffeEPFTimetableBundle:Subject')->findAll();
        $subjects = array();
        foreach($dbSubjects as $subject)
        {
            $subjects[$subject->getCode()] = $subject->getFullName();
        }
        unset($dbSubjects);
        unset($subject);

        /*
         * Setting color Ids to use with Google Calendar
         */
        $colorIds = array('CM' => 5, 'Examens' => 11);

        // Todo: what happens if I try to login with a bogus username?
        $baseLoginUrl = $this->getContainer()->getParameter('heffe_epf_timetable.helato_login_url');
        $epfNextUrl = $this->getContainer()->getParameter('heffe_epf_timetable.helato_next_url');
        $epfWeekDayUrl = $this->getContainer()->getParameter('heffe_epf_timetable.helato_weekday_url');
        if(empty($baseLoginUrl) || empty($epfNextUrl) || empty($epfWeekDayUrl) )
        {
            $output->writeln('EPF URL setup routine failed. Please ensure you correctly filled file \'parameters.yml\'');
            return;
        }

        $NB_WEEKS_TO_SYNC = $this->getContainer()->getParameter('heffe_epf_timetable.weeks_to_sync');
        if( empty($NB_WEEKS_TO_SYNC) || is_numeric($NB_WEEKS_TO_SYNC) == false)
        {
            $output->writeln('heffe_epf_timetable.weeks_to_sync parameter has an invalid numeric value. Please check your \'parameters.yml\' file.');
            return;
        }
        $logger->addDebug(sprintf("heffe_epf_timetable.weeks_to_sync parameter set to %d", $NB_WEEKS_TO_SYNC));

        /**
         * For each user who signed up for the service, proceed and fetch the timetable from EPF Website
         */
        foreach($users as $user)
        {

            if($user->getUsername() == null)
                continue;

            $output->writeln(sprintf("Starting with user %s", $user->getUsername()));

            $client->get($baseLoginUrl . $user->getUsername());

            $weeks = array();
            for($w = 0; $w < $NB_WEEKS_TO_SYNC; $w++)
            {
                $weeks[ $w ] = $client->get( $epfWeekDayUrl );
                if( $w < $NB_WEEKS_TO_SYNC - 1)
                {
                    $client->get( $epfNextUrl );
                }
            }

            $epfEvents = array();

            foreach($weeks as $week)
            {
                // Finding out info about the week itself (startdate and enddate)
                $bPlanningDate = false;
                $startPos = strpos($week, "<td class=\"PLANNING_TITLE\"");
                if($startPos !== false)
                {
                    $endPos = strpos($week, "</td>", $startPos);
                    if($endPos !== false)
                    {
                        $titleSub = substr($week, $startPos, $endPos - $startPos + 1);
                        $matches = array();
                        if(preg_match('/du (\d{2}-\d{2}-\d{4}) au (\d{2}-\d{2}-\d{4})/', $titleSub, $matches) === 1)
                        {
                            $startDate = \DateTime::createFromFormat('d-m-Y',$matches[1]);
                            $endDate = \DateTime::createFromFormat('d-m-Y', $matches[2]);
                            $bPlanningDate = true;
                        }
                    }
                }

                /**
                 * If we weren't able to figure out the startDate & endDate of the timetable, then it's safe to assume
                 * that the page layout changed and that we shouldn't proceed further
                */
                if($bPlanningDate === false)
                {
                    $logger->addError(sprintf("Timetable html structure not recognized for user '%s'", $user->getUsername()));
                    continue;
                }

                /**
                 * To fetch timetable details, we're looking for all <input> fields... since that's where the details
                 * are stored.
                 */
                $matches = array();
                if(preg_match_all('/<input type=\'hidden\' id=\'txt_block_\d+\' value=(["\'])([^\1]*?)\1/i', $week, $matches))
                {
                    $details = $matches[2];
                    for($i=0; $i<count($details); $i+=11)
                    {
                        /*
                         *  Here's a bit of documentation about the various offsets:
                         *      0 :: Name
                         *      1 :: Info
                         *      2 :: Place
                         *      3 :: Comment
                         *      4 :: Type
                         *      5 :: Value
                         *      6 :: Master
                         *      7 :: Double
                         *      8 :: Day
                         *      9 :: HDeb
                         *      10 :: HFin
                         */
                        $iName = $details[$i];
                        // Fixing name display issues
                        $iName = mb_convert_encoding($iName, 'UTF-8', 'ISO-8859-15');
                        $iName = preg_replace('/\d{2}:\d{2}/', '', $iName);
                        $iName = str_replace('&#160;', '', $iName);

                        $iLocation = $details[$i+2];
                        $iLocation = trim(trim($iLocation, '-'));

                        $iType = mb_convert_encoding($details[$i+4], 'UTF-8', 'ISO-8859-15');
                        $iValue = mb_convert_encoding($details[$i+5], 'UTF-8', 'ISO-8859-15');

                        $iMaster = mb_convert_encoding($details[$i+6], 'UTF-8', 'ISO-8859-15');
                        $iMaster = trim($iMaster, '-');

                        $iDay = $details[$i+8];
                        $iStart = $details[$i+9];
                        $iEnd = $details[$i+10];

                        /**
                         * Trying to guess the class type based on the info given on the website
                         */
                        if(strpos($iType, '(TP)') !== false)
                        {
                            $iClassType = 'TP';
                        }
                        else if(strpos($iType, '(TD)') !== false)
                        {
                            $iClassType = 'TD';
                        }
                        else if(strpos($iType, '(CM)') !== false)
                        {
                            $iClassType = 'CM';
                        }
                        else
                        {
                            $iClassType = 'Unknown';
                        }

                        /**
                         * Each subject has a unique subject code, which is also used on the Moodle platform.
                         *
                         * We're storing a dictionary of subjects and codes in the database. That way we can easily
                         * customize subject names and stop relying on those provided on the main timetable page.
                         */
                        $res = preg_match("/([^\s]+)/", $iType, $mtch);
                        if ($res !== false)
                        {
                            $subjectCode = $mtch[1];
                            if(isset($subjects[$subjectCode]))
                            {
                                $iName = $subjects[$subjectCode];
                            }
                        }


                        $ev = new \Google_Event();



                        $ev->setSummary( sprintf("%s - %s", $iClassType, $iName) );
                        $ev->setLocation($iLocation);

                        if(isset($colorIds[$iClassType]))
                        {
                            $ev->setColorId($colorIds[$iClassType]);
                        }

                        if( strpos($iName, "Examens") !== false )
                        {
                            $ev->setColorId($colorIds['Examens']);
                        }

                        $timeZone = new \DateTimeZone('Europe/Paris');
                        $dtStart = \DateTime::createFromFormat("Ymd G:i", $iDay . ' ' . $iStart, $timeZone  );
                        $dtEnd = \DateTime::createFromFormat("Ymd G:i", $iDay . ' ' . $iEnd, $timeZone  );

                        $timeZoneOffset = sprintf("%+03d:%02d",$timeZone->getOffset($dtStart) / 3600, $timeZone->getOffset($dtStart)%3600);

                        $start = new \Google_EventDateTime();
                        $start->setDateTime($dtStart->format('Y-m-d').'T'.$dtStart->format('H:i:s').$timeZoneOffset);
                        $start->setTimeZone('Europe/Paris');
                        $ev->setStart($start);
                        if($i == 0)
                        {
                            // First event
                            $timeMin = new \Google_EventDateTime();
                            $timeMin->setDateTime($dtStart->format('Y-m-d').'T'.'00:00:00');
                            $timeMin->setTimeZone('Europe/Paris');
                        }


                        $end = new \Google_EventDateTime();
                        $end->setDateTime($dtEnd->format('Y-m-d').'T'.$dtEnd->format('H:i:s').$timeZoneOffset);
                        $end->setTimeZone('Europe/Paris');
                        $ev->setEnd($end);
                        if($i == count($epfEvents) - 1)
                        {
                            //Last event
                            $timeMax = $end;
                        }

                        $ev->setDescription(sprintf("Prof. : %s", $iMaster));

                        $epfEvents[] = $ev;
                    }
                }
            }

            $output->writeln("Setting Access Token...");
            $googleService->getClient()->setUseObjects(true);
            $googleService->getClient()->setAccessToken($user->getAccessToken());
            $output->writeln("Access Token set...");
            if($googleService->getClient()->isAccessTokenExpired())
            {
                // Refresh access token
                $tokenObj = json_decode($user->getAccessToken(), true);
                if($tokenObj === null)
                {
                    // Invalid access token, moving on to next user
                    // Todo: Do something useful instead, like disable the user and notify him of token issue

                    $logger->addWarning(sprintf('Invalid access token for user \'%1$s\' (id: %2$d)', $user->getUsername(), $user->getId()));
                    continue;
                }
                $googleService->getClient()->refreshToken($tokenObj['refresh_token']);
                $user->setAccessToken($googleService->getClient()->getAccessToken());
                $em->persist($user);
                $em->flush();
            }

            /**
             * @var \Google_CalendarService() $calendar
             */
            $calendar = $googleService->getCalendar();


            $googleEvents = array();

            /**
             * @var \Google_Events $googleEventsRequest
             */
            //Fetching Calendar events for the calendar the user selected
            $googleEventsRequest = $calendar->events->listEvents($user->getCalendarId());


            while( count($googleEventsRequest->items) > 0 )
            {
                foreach($googleEventsRequest->getItems() as $event)
                {
                    $googleEvents[] = $event;
                }
                $pageToken = $googleEventsRequest->getNextPageToken();
                if($pageToken)
                {
                    $optParams = array('pageToken' => $pageToken);
                    $googleEventsRequest = $calendar->events->listEvents($user->getCalendarId(), $optParams);
                }
                else
                {
                    break;
                }
            }

            /**
             * @var \Google_Event $gEvent
             * @var \Google_Event $epfEvent
             */
            $eventsToInsert = array();
            /*foreach($googleEvents as $gEvent)
            {
                foreach($epfEvents as $epfEvent)
                {
                    // Trying to find that Google Event

                    // If the event is nowhere to be found, then that means the Event from Google Calendar isn't on EPF Calendar
                    // We could assume that this event has been removed
                }
            }*/

            foreach ($epfEvents as $epfEvent)
            {
                $bEventFound = false;
                foreach ($googleEvents as $gEvent)
                {
                    // Trying to find that EPF Event
                    // If the event is nowhere to be found, then that means the Event from EPF Calendar has yet to be imported on Google Calendar

                    if($gEvent->getStart() == $epfEvent->getStart() && $gEvent->getEnd() == $epfEvent->getEnd() && $gEvent->getSummary() == $epfEvent->getSummary())
                    {
                        $bEventFound = true;
                        break;
                    }
                }
                if( $bEventFound == false )
                {
                    // Didn't find the event
                    $eventsToInsert[] = $epfEvent;
                }
            }

            //Inserting events in the calendar
            $i = 0;
            foreach($eventsToInsert as $epfEvent)
            {
                /*
                 * Throttle feature. We'll let the process sleep for half a second every 5 calls
                 */
                if(($i+1) % 5 == 0)
                {
                    sleep(0.5);
                }

                $calendar->events->insert($user->getCalendarId(), $epfEvent);
                $i++;
            }

            $output->writeln(sprintf("Inserted %d events", $i));
            $logger->addInfo(sprintf('Inserted %1$d events for user \'%2$s\' (id: %3$s)', $i, $user->getUsername(), $user->getId()));
        }
    }
}