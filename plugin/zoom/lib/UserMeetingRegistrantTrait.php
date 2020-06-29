<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\UserBundle\Entity\User;
use Database;

/**
 * Trait UserMeetingRegistrantTrait.
 * A Zoom meeting registrant linked to a local user.
 * The user id is stored in the registrant's email address on write operations, read and removed on retrieval.
 *
 * @package Chamilo\PluginBundle\Zoom
 */
trait UserMeetingRegistrantTrait
{
    /** @var bool whether the remote zoom record contains a local user's identifier */
    public $isTaggedWithUserId;

    /** @var int */
    public $userId;

    /** @var User */
    public $user;

    /** @var string */
    public $fullName;

    public function loadUser()
    {
        $this->user = Database::getManager()->getRepository('ChamiloUserBundle:User')->find($this->userId);
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function tagEmail()
    {
        $this->email = str_replace('@', $this->getTag(), $this->getUntaggedEmail());
    }

    public function untagEmail()
    {
        $this->email = $this->getUntaggedEmail();
    }

    public function matches($userId)
    {
        return $userId == $this->userId;
    }

    public function computeFullName()
    {
        $this->fullName = api_get_person_name($this->first_name, $this->last_name);
    }

    protected function decodeAndRemoveTag()
    {
        $this->isTaggedWithUserId = preg_match(self::getTagPattern(), $this->email, $matches);
        if ($this->isTaggedWithUserId) {
            $this->setUserId($matches['userId']);
            $this->untagEmail();
        } else {
            $this->setUserId(0);
        }
        $this->user = null;
    }

    protected function getUntaggedEmail()
    {
        return str_replace($this->getTag(), '@', $this->email);
    }

    /**
     * @return string a tag to append to a registrant comments so to link it to a user
     */
    private function getTag()
    {
        return "+user_$this->userId@";
    }

    private static function getTagPattern()
    {
        return '/\+user_(?P<userId>\d+)@/m';
    }
}
