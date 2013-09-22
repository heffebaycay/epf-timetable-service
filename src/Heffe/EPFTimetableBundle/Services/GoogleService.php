<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Fabien
 * Date: 31/01/13
 * Time: 17:18
 * To change this template use File | Settings | File Templates.
 */

namespace Heffe\EPFTimetableBundle\Services;

class GoogleService
{
    protected $client;

    protected $calendar;
    protected $oauth2;

    protected $token;

    public function __construct($contribDir, $appName, $clientId, $clientSecret, $devKey, $redirectUri)
    {
        require_once $contribDir . DIRECTORY_SEPARATOR . 'Google_Oauth2Service.php';
        require_once $contribDir . DIRECTORY_SEPARATOR . 'Google_CalendarService.php';

        $this->client = new \Google_Client();
        $this->client->setApplicationName($appName);
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setDeveloperKey($devKey);
        $this->client->setRedirectUri($redirectUri);

        $this->calendar = new \Google_CalendarService($this->client);
        $this->oauth2 = new \Google_Oauth2Service($this->client);
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getCalendar()
    {
        return $this->calendar;
    }

    public function getOAuth2()
    {
        return $this->oauth2;
    }

    public function createAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function setAccessToken($access_token)
    {
        $this->client->setAccessToken($access_token);
    }
}