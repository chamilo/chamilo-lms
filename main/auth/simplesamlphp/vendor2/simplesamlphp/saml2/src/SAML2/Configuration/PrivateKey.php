<?php

namespace SAML2\Configuration;

use SAML2\Exception\InvalidArgumentException;
use SAML2\Exception\RuntimeException;

/**
 * Configuration of a private key.
 */
class PrivateKey extends ArrayAdapter
{
    const NAME_NEW     = 'new';
    const NAME_DEFAULT = 'default';

    /**
     * @var string
     */
    private $filePathOrContents;

    /**
     * @var string
     */
    private $passphrase;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $isFile;

    /**
     * Constructor for PrivateKey.
     *
     * @param string $filePathOrContents
     * @param string $name
     * @param string|null $passphrase
     * @param bool $isFile
     * @throws \Exception
     */
    public function __construct($filePathOrContents, $name, $passphrase = null, $isFile = true)
    {
        if (!is_string($filePathOrContents)) {
            throw InvalidArgumentException::invalidType('string', $filePathOrContents);
        }

        if (!is_string($name)) {
            throw InvalidArgumentException::invalidType('string', $name);
        }

        if ($passphrase && !is_string($passphrase)) {
            throw InvalidArgumentException::invalidType('string', $passphrase);
        }

        $this->filePathOrContents = $filePathOrContents;
        $this->passphrase = $passphrase;
        $this->name = $name;
        $this->isFile = $isFile;
    }


    /**
     * @return string
     */
    public function getFilePath()
    {
        if (!$this->isFile()) {
            throw new RuntimeException('No path provided.');
        }

        return $this->filePathOrContents;
    }


    /**
     * @return bool
     */
    public function hasPassPhrase()
    {
        return (bool) $this->passphrase;
    }


    /**
     * @return string
     */
    public function getPassPhrase()
    {
        return $this->passphrase;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getContents()
    {
        if ($this->isFile()) {
            throw new RuntimeException('No contents provided');
        }

        return $this->filePathOrContents;
    }

    /**
     * @return bool
     */
    public function isFile()
    {
        return $this->isFile;
    }
}
