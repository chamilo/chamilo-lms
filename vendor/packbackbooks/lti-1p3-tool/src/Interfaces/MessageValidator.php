<?php
namespace Packback\Lti1p3\Interfaces;

interface MessageValidator
{
    public function validate(array $jwtBody);
    public function canValidate(array $jwtBody);
}
