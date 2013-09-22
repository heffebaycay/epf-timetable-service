<?php

namespace Heffe\EPFTimetableBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table(name="epf_users")
 * @ORM\Entity(repositoryClass="Heffe\EPFTimetableBundle\Entity\UserRepository")
 */
class User
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=true)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="google_id", type="string", length=255)
     */
    private $googleId;

    /**
     * @var string
     *
     * @ORM\Column(name="google_email", type="string", length=255)
     */
    private $googleEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="calendar_id", type="string", length=255, nullable=true)
     */
    private $calendarId;


    /**
     * @var string
     *
     * @ORM\Column(name="access_token", type="text")
     */
    private $accessToken;

    /**
     * @var boolean
     *
     * @ORM\Column(name="validated", type="boolean")
     */
    private $validated;

    /**
     * @var string
     *
     * @ORM\Column(name="validation_code", type="string", length=10, nullable=true)
     */
    private $validationCode;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;
    
        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set googleId
     *
     * @param string $googleId
     * @return User
     */
    public function setGoogleId($googleId)
    {
        $this->googleId = $googleId;
    
        return $this;
    }

    /**
     * Get googleId
     *
     * @return string 
     */
    public function getGoogleId()
    {
        return $this->googleId;
    }

    /**
     * Set googleEmail
     *
     * @param string $googleEmail
     * @return User
     */
    public function setGoogleEmail($googleEmail)
    {
        $this->googleEmail = $googleEmail;
    
        return $this;
    }

    /**
     * Get googleEmail
     *
     * @return string 
     */
    public function getGoogleEmail()
    {
        return $this->googleEmail;
    }

    /**
     * Set calendarId
     *
     * @param string $calendarId
     * @return User
     */
    public function setCalendarId($calendarId)
    {
        $this->calendarId = $calendarId;
    
        return $this;
    }

    /**
     * Get calendarId
     *
     * @return string 
     */
    public function getCalendarId()
    {
        return $this->calendarId;
    }

    /**
     * Set accessToken
     *
     * @param string $accessToken
     * @return User
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    
        return $this;
    }

    /**
     * Get accessToken
     *
     * @return string 
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set validated
     *
     * @param boolean $validated
     * @return User
     */
    public function setValidated($validated)
    {
        $this->validated = $validated;
    
        return $this;
    }

    /**
     * Get validated
     *
     * @return boolean 
     */
    public function getValidated()
    {
        return $this->validated;
    }

    /**
     * Set validationCode
     *
     * @param string $validationCode
     * @return User
     */
    public function setValidationCode($validationCode)
    {
        $this->validationCode = $validationCode;
    
        return $this;
    }

    /**
     * Get validationCode
     *
     * @return string 
     */
    public function getValidationCode()
    {
        return $this->validationCode;
    }
}