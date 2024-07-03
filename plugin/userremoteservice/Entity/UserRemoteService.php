<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\UserRemoteService;

use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * UserRemoteService.
 *
 * @ORM\Table(name="plugin_user_remote_service")
 * @ORM\Entity
 */
class UserRemoteService
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    protected $url;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return UserRemoteService
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return UserRemoteService
     */
    public function setURL($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccessURL($pluginName)
    {
        $accessUrl = api_get_path(WEB_PLUGIN_PATH).$pluginName."/redirect.php?serviceId=".$this->getId();

        return $accessUrl;
    }

    /**
     * Returns a user-specific URL, with two extra query string parameters : 'username' and 'hash'.
     * 'hash' is generated using $salt and $userId.
     *
     * @param string $username the URL query parameter 'username'
     * @param string $userId   the user identifier, to build the hash
     * @param string $salt     the salt, to build the hash
     *
     * @throws Exception on hash generation failure
     *
     * @return string the custom user URL
     */
    public function getCustomUserURL($username, $userId, $salt)
    {
        $hash = password_hash($salt.$userId, PASSWORD_BCRYPT);
        if (false === $hash) {
            throw new Exception('hash generation failed');
        }

        return sprintf(
            '%s%s%s',
            $this->url,
            false === strpos($this->url, '?') ? '?' : '&',
            http_build_query(
                [
                    'username' => $username,
                    'hash' => $hash,
                ]
            )
        );
    }

    /**
     * Returns a user-specific URL, with two extra query string parameters : 'uid' and 'hash'.
     * 'hash' is generated using $salt and $userId.
     *
     * @param string $userId the user identifier, to build the hash and to include for the uid parameter
     * @param string $salt   the salt, to build the hash
     *
     * @throws Exception on hash generation failure
     *
     * @return string the custom user redirect URL
     */
    public function getCustomUserRedirectURL($userId, $salt)
    {
        $hash = password_hash($salt.$userId, PASSWORD_BCRYPT);
        if (false === $hash) {
            throw new Exception('hash generation failed');
        }

        return sprintf(
            '%s%s%s',
            $this->url,
            false === strpos($this->url, '?') ? '?' : '&',
            http_build_query(
                [
                    'uid' => $userId,
                    'hash' => $hash,
                ]
            )
        );
    }
}
