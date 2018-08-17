<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\ImsLti;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ImsLtiTool
 *
 * @ORM\Table(name="plugin_ims_lti_tool")
 * @ORM\Entity()
 */
class ImsLtiTool
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    private $name = '';
    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description = null;
    /**
     * @var string
     *
     * @ORM\Column(name="launch_url", type="string")
     */
    private $launchUrl = '';
    /**
     * @var string
     *
     * @ORM\Column(name="consumer_key", type="string")
     */
    private $consumerKey = '';
    /**
     * @var string
     *
     * @ORM\Column(name="shared_secret", type="string")
     */
    private $sharedSecret = '';
    /**
     * @var string|null
     *
     * @ORM\Column(name="custom_params", type="text", nullable=true)
     */
    private $customParams = null;
    /**
     * @var bool
     *
     * @ORM\Column(name="is_global", type="boolean")
     */
    private $isGlobal = false;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ImsLtiTool
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param null|string $description
     * @return ImsLtiTool
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getLaunchUrl()
    {
        return $this->launchUrl;
    }

    /**
     * @param string $launchUrl
     * @return ImsLtiTool
     */
    public function setLaunchUrl($launchUrl)
    {
        $this->launchUrl = $launchUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    /**
     * @param string $consumerKey
     * @return ImsLtiTool
     */
    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getSharedSecret()
    {
        return $this->sharedSecret;
    }

    /**
     * @param string $sharedSecret
     * @return ImsLtiTool
     */
    public function setSharedSecret($sharedSecret)
    {
        $this->sharedSecret = $sharedSecret;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getCustomParams()
    {
        return $this->customParams;
    }

    /**
     * @param null|string $customParams
     * @return ImsLtiTool
     */
    public function setCustomParams($customParams)
    {
        $this->customParams = $customParams;

        return $this;
    }

    /**
     * @return bool
     */
    public function isGlobal()
    {
        return $this->isGlobal;
    }

    /**
     * @param bool $isGlobal
     * @return ImsLtiTool
     */
    public function setIsGlobal($isGlobal)
    {
        $this->isGlobal = $isGlobal;

        return $this;
    }

    /**
     * @return array
     */
    public function parseCustomParams()
    {
        $strings = explode($this->customParams, "\n");
        $pairs = explode('=', $strings);

        return [
            'key' => 'custom_'.$pairs[0],
            'value' => $pairs[1]
        ];
    }
}
