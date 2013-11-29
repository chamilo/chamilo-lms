<?php

namespace SilexOpauth\Security;

/**
 * @author Rafal Lindemann
 *  */
class OpauthResult implements \Serializable
{

    protected $auth;

    public function __construct($auth)
    {
        $this->auth = $auth;
    }


    public function getAuth()
    {
        return $this->auth;
    }


    public function getUid()
    {
        return $this->auth['uid'];
    }


    public function getToken()
    {
        return $this->auth['credentials']['token'];
    }


    public function getExpiry()
    {
        return strtotime($this->auth['credentials']['expires']);
    }


    public function getInfo()
    {
        return $this->auth['info'];
    }


    public function getRaw()
    {
        return $this->auth['info'];
    }


    public function getProvider()
    {
        return $this->auth['provider'];
    }


    public function getName()
    {
        return $this->auth['info']['name'];
    }


    public function getEmail()
    {
        return $this->auth['info']['email'];
    }


    public function getNickname()
    {
        return isset($this->auth['info']['nickname']) ? $this->auth['info']['nickname'] : null;
    }


    public function getPicture()
    {
        return isset($this->auth['info']['image']) ? $this->auth['info']['image'] : null;
    }


    public function serialize()
    {
        return serialize($this->auth);
    }


    public function unserialize($serialized)
    {
        $this->auth = unserialize($serialized);
    }


}