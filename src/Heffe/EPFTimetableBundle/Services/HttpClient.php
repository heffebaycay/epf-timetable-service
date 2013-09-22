<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Fabien
 * Date: 15/01/13
 * Time: 14:54
 * To change this template use File | Settings | File Templates.
 */
namespace Heffe\EPFTimetableBundle\Services;

class HttpClient
{
    private $cookieFile;

    public function __construct()
    {
        $this->cookieFile = tempnam(sys_get_temp_dir(), "epf_");
    }

    public function get($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE,  $this->cookieFile);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $output = curl_exec($ch);
        curl_close($ch);
        unset($ch);

        return $output;
    }

    public function post($url, $data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE,  $this->cookieFile);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $output = curl_exec($ch);
        curl_close($ch);
        unset($ch);

    }
}