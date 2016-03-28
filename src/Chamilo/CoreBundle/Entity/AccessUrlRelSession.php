<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccessUrlRelSession
 *
 * @ORM\Table(name="access_url_rel_session")
 * @ORM\Entity
 */
class AccessUrlRelSession
{
    /**
     * @var integer
     *
     * @ORM\Column(name="access_url_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $accessUrlId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $sessionId;

    /**
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return AccessUrlRelSession
     */
    public function setAccessUrlId($accessUrlId)
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    /**
     * Get accessUrlId
     *
     * @return integer
     */
    public function getAccessUrlId()
    {
        return $this->accessUrlId;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return AccessUrlRelSession
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
}
