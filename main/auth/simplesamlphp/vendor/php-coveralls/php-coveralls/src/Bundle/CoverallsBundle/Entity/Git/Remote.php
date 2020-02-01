<?php

namespace PhpCoveralls\Bundle\CoverallsBundle\Entity\Git;

use PhpCoveralls\Bundle\CoverallsBundle\Entity\Coveralls;

/**
 * Remote info.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class Remote extends Coveralls
{
    /**
     * Remote name.
     *
     * @var null|string
     */
    protected $name;

    /**
     * Remote URL.
     *
     * @var null|string
     */
    protected $url;

    // API

    /**
     * {@inheritdoc}
     *
     * @see \PhpCoveralls\Bundle\CoverallsBundle\Entity\ArrayConvertable::toArray()
     */
    public function toArray()
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
        ];
    }

    // accessor

    /**
     * Set remote name.
     *
     * @param string $name remote name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Return remote name.
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set remote URL.
     *
     * @param string $url remote URL
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Return remote URL.
     *
     * @return null|string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
