<?php

namespace Heffe\EPFTimetableBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="epf_home")
    */
    public function indexAction()
    {
        $googleService = $this->container->get('heffe_epf_timetable.googleservice');
        $authUrl = $googleService->createAuthUrl();

        return $this->render('HeffeEPFTimetableBundle:Default:index.html.twig', array('authUrl' => $authUrl ) );
    }

    /**
     * @Route("/auth", name="oauth2_auth")
     */
    public function authAction()
    {
        $request = $this->getRequest();
        $session = $request->getSession();
        $code = $request->get('code');

        $googleService = $this->container->get('heffe_epf_timetable.googleservice');

        if($request->get('logout') != null)
        {
            $session->remove('access_token');
        }

        if(!empty($code))
        {
            $googleService->getClient()->authenticate();

            $session->set('access_token', $googleService->getClient()->getAccessToken());

            return $this->redirect($this->generateUrl('user_setup'));
        }


        die("Not implemented");
    }

    /**
     * @Route("/setup", name="user_setup")
     */
    public function setupUserAction()
    {
        $request = $this->getRequest();
        $session = $request->getSession();
        $googleService = $this->container->get('heffe_epf_timetable.googleservice');

        if($request->request->get('logout') != null)
        {
            $session->remove('access_token');
            die('Loggedout');
        }

        if($session->get('access_token') != null)
        {
            $em = $this->getDoctrine()->getManager();
            $googleService->getClient()->setAccessToken($session->get('access_token'));
            $oauth = $googleService->getOAuth2();

            $userDetails = $oauth->userinfo->get();

            $googleId = $userDetails['id'];
            $googleEmail = $userDetails['email'];

            if(empty($googleId))
            {
                throw new \Exception('Auth sequence failed');
            }

            $session->set('googleId', $googleId);

            $userRepo = $this->getDoctrine()->getRepository('HeffeEPFTimetableBundle:User');
            $user = $userRepo->findOneBy(array('googleId' => $googleId));

            if($user == null)
            {
                $user = new \Heffe\EPFTimetableBundle\Entity\User();
                $user->setGoogleId($googleId);
                $user->setGoogleEmail($googleEmail);
                $user->setValidated(false);
            }
            $user->setAccessToken($session->get('access_token'));
            $em->persist($user);
            $em->flush();

            if($user->getValidated() === false)
            {
                // Redirect to validation page
                return $this->render('HeffeEPFTimetableBundle:Auth:validation-step1.html.twig', array('user' => $user));
            }

            $calendars = array();
            $i=0;
            $calendarsList = $googleService->getCalendar()->calendarList->listCalendarList();
            foreach($calendarsList['items'] as $calendar)
            {
                if($calendar['accessRole'] == 'writer' || $calendar['accessRole'] == 'owner')
                {
                    $calendars[$i]['id'] = $calendar['id'];
                    $calendars[$i]['summary'] = $calendar['summary'];
                    $i++;
                }
            }



            return $this->render('HeffeEPFTimetableBundle:Auth:step2.html.twig', array('user' => $user,
                                                                                       'calendars' => $calendars,
                                ));

        }



        die("Not implemented");
    }

    /**
     * @Route("/setup/save", name="user_setup_save")
     */
    public function saveUserSetupAction()
    {
        $em = $this->getDoctrine()->getManager();
        $request = $this->getRequest();
        $session = $request->getSession();

        $googleId = $session->get('googleId');

        if(!empty($googleId) && $googleId == $request->get('googleId'))
        {
            $userRepo = $this->getDoctrine()->getRepository('HeffeEPFTimetableBundle:User');
            $user = $userRepo->findOneBy(array('googleId' => $googleId));
            if($user != null)
            {
                $username = $request->request->get('username');
                if(!empty($username))
                {
                    $user->setUsername($username);
                }

                $calendars = $request->request->get('calendars');
                if(!empty($calendars))
                {
                    $user->setCalendarId($calendars);
                }

                $em->persist($user);
                $em->flush();

                //@Todo: Figure out a way to trigger the timetable fetching process.
                // Idea: Add a "Job" table that would contain the list of jobs ToDO
                // Ideally, a cron service could check the content of that table every X minutes
                // That service would need to use Mutex to avoid multiple instances of the process
                // Service would essentially run the

            }
            else
            {
                throw new \Exception('Unable to find user');
            }
        }
        else
        {
            throw new \Exception('Invalid form data');
        }

        $session->getFlashBag()->add('success', "Données du formulaire enregistrées");

        return $this->redirect($this->generateUrl('user_setup'));
    }

    /**
     * @Route("/setup/validation/send", name="validation_step2")
     */
    public function validationStep2Action()
    {
        $em = $this->getDoctrine()->getManager();
        $session = $this->getRequest()->getSession();

        $googleId = $session->get('googleId');
        if(empty($googleId))
        {
            throw new \Exception("User not logged in");
        }

        /**
         * @var \Heffe\EPFTimetableBundle\Entity\User $user
         */
        $user = $this->getDoctrine()->getRepository('HeffeEPFTimetableBundle:User')->findOneBy(array('googleId' => $googleId));
        if($user == null)
        {
            throw new \Exception("Unknown user");
        }

        if($user->getValidated() === false)
        {
            $email = $this->getRequest()->request->get('email');
            if(empty($email))
            {
                $session->getFlashBag()->add('error', "Ce service nécessite que vous entriez une adresse @epfedu.fr valide.");
                return $this->redirect($this->generateUrl('user_setup'));
            }
            if(substr($email, -10) != '@epfedu.fr')
            {
                $session->getFlashBag()->add('error', 'Ce service nécessite que vous possédiez une adresse @epfedu.fr.');
                return $this->redirect($this->generateUrl('user_setup'));
            }

            // Generating validation code

            $alphabet = range('A','Z');
            $numbers = range(0,9);
            $validCharacters = '';
            foreach($alphabet as $letter)
            {
                $validCharacters .= $letter;
            }
            foreach($numbers as $number)
            {
                $validCharacters .= $letter;
            }
            unset($number);
            unset($letter);
            unset($alphabet);
            unset($numbers);

            $validationCode = '';
            for($i=0; $i<7; $i++)
            {
                $index = mt_rand(0, strlen($validCharacters)-1);
                $validationCode .= $validCharacters[$index];
            }

            $user->setValidationCode($validationCode);
            $em->persist($user);
            $em->flush();

            $epfEmailFrom = $this->container->getParameter('epf_validation_email_from');
            if(empty($epfEmailFrom))
            {
                throw new \Exception("Adresse e-mail d'expédition non configurée");
            }

            //Sending email
             $message = \Swift_Message::newInstance()
                        ->setSubject("Validation de l'accès au service EPF Timetable Updater")
                        ->setFrom($epfEmailFrom)
                        ->setTo($email)
                        ->setBody(
                            $this->renderView('HeffeEPFTimetableBundle:Email:validation.html.twig',
                            array('code' => $validationCode, 'googleEmail' => $user->getGoogleEmail(), 'googleId' => $googleId)
                            ),
                            'text/html'
                        )
                        ->addPart(
                            $this->renderView('HeffeEPFTimetableBundle:Email:validation.txt.twig',
                            array('code' => $validationCode, 'googleEmail' => $user->getGoogleEmail(), 'googleId' => $googleId)
                            ),
                            'text/plain'
                        )
            ;

            if ($this->get('mailer')->send($message))
            {
                $session->getFlashBag()->add('success', sprintf("Un message contenant un lien de validation a été envoyé à l'adresse %s", $email ));
            }
            else
            {
                $session->getFlashBag()->add('error', sprintf("Echec de l'envoi du courriel de validation à l'adresse %s", $email));
                return $this->redirect($this->generateUrl('validation_step2'));
            }

            $session->set('firstTimeSeeingValidationForm', true);
            return $this->redirect($this->generateUrl('validation_step3'));
        }

    }

    /**
     * @Route("/setup/validation/check", name="validation_step3", defaults={"pGoogleId"=null, "validationCode"=null})
     * @Route("/setup/validation/check/{pGoogleId}/{pValidationCode}",name="validation_step3_from_mail")
     */
    public function validationStep3Action($pGoogleId=null, $pValidationCode=null)
    {
        $em = $this->getDoctrine()->getManager();
        $request = $this->getRequest();
        $session = $request->getSession();

        if(!empty($pGoogleId))
        {
            $googleId = $pGoogleId;
        }
        else if($session->has('googleId'))
        {
            $googleId = $session->get('googleId');
        }
        else
        {
            throw new \Exception("No user specified");
        }

         /**
         * @var \Heffe\EPFTimetableBundle\Entity\User $user
         */
        $user = $this->getDoctrine()->getRepository('HeffeEPFTimetableBundle:User')->findOneBy(array('googleId' => $googleId));
        if($user == null)
        {
            throw new \Exception("Unknown user");
        }

        if(!empty($pValidationCode))
        {
            $validationCode = $pValidationCode;
        }
        else if($request->request->get('validationCode') != null && $request->request->get('validationCode') != '')
        {
            $validationCode = $request->request->get('validationCode');
        }
        else
        {
            if($session->has('googleId'))
            {
                if($session->get('firstTimeSeeingValidationForm') == true)
                {
                    $session->remove('firstTimeSeeingValidationForm');
                }
                else
                {
                    $session->getFlashBag()->add('error', "Code de validation non-valide (40)");
                }

                return $this->render('HeffeEPFTimetableBundle:Auth:validation-step2.html.twig', array('user' => $user));
            }
            else
            {
                $session->getFlashBag()->add('error', "Code de validation non-valide.");
                return $this->redirect($this->generateUrl('epf_home'));
            }
        }


        if($user->getValidated() == false)
        {
            if($validationCode == $user->getValidationCode())
            {
                // User has submitted the right validation code
                $user->setValidated(true);
                $em->persist($user);
                $em->flush();
                if($session->has('googleId'))
                {
                    $session->getFlashBag()->add('success', "Votre compte a été validé.");
                    return $this->redirect($this->generateUrl('user_setup'));
                }
                else
                {
                    $session->getFlashBag()->add('success', "Votre compte a été validé. Vous pouvez maintenant vous connecter.");
                    return $this->redirect($this->generateUrl('epf_home'));
                }

            }
            else
            {
                $session->getFlashBag()->add('error', "Code de validation non-valide");
                if($session->has('googleId'))
                {
                    return $this->render('HeffeEPFTimetableBundle:Auth:validation-step2.html.twig', array('user' => $user));
                }
                else
                {
                    return $this->redirect($this->generateUrl('epf_home'));
                }

            }
        }
        else
        {
            $session->getFlashBag()->add('notice', "Votre compte utilisateur a déjà été activé");
            return $this->redirect($this->generateUrl('user_setup'));
        }
    }
}
