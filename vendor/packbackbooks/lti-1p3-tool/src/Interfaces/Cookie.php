<?php
namespace Packback\Lti1p3\Interfaces;

interface Cookie
{
    public function getCookie($name);
    public function setCookie($name, $value, $exp = 3600, $options = []);
}
