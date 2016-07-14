<?php

namespace Ddeboer\DataImport\Tests\Fixtures\Entity;

/**
 * @Entity()
 */
class User
{
    /** @Id @GeneratedValue @Column(type="integer") **/
    private $id;

    /** @Column(type="string") */
    private $username;

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }
}
