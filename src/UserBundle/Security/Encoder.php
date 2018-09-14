<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Security;

use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;

/**
 * Class Encoder.
 *
 * @package Chamilo\UserBundle\Security
 */
class Encoder extends BasePasswordEncoder
{
    private $passwordEncrypt;

    /**
     * @param string $passwordEncrypt
     */
    public function __construct(string $passwordEncrypt)
    {
        $passwordEncrypt = str_replace("'", '', trim($passwordEncrypt));
        $this->passwordEncrypt = $passwordEncrypt;
    }

    /**
     * @param string $raw
     * @param string $salt
     *
     * @return string
     */
    public function encodePassword($raw, $salt)
    {
        $defaultEncoder = $this->getEncoder();
        $encoded = $defaultEncoder->encodePassword($raw, $salt);

        return $encoded;
    }

    /**
     * @param string $encoded
     * @param string $raw
     * @param string $salt
     *
     * @return bool
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        if ($this->isPasswordTooLong($raw)) {
            return false;
        }

        $encoder = $this->getEncoder();

        return $encoder->isPasswordValid($encoded, $raw, $salt);
    }

    /**
     * @return BCryptPasswordEncoder|MessageDigestPasswordEncoder|PlaintextPasswordEncoder
     */
    private function getEncoder()
    {
        switch ($this->passwordEncrypt) {
            case 'none':
                $defaultEncoder = new PlaintextPasswordEncoder();
                break;
            case 'bcrypt':
                $defaultEncoder = new BCryptPasswordEncoder(4);
                break;
            case 'sha1':
            case 'md5':
                $defaultEncoder = new MessageDigestPasswordEncoder($this->method, false, 1);
                break;
        }

        return $defaultEncoder;
    }
}
