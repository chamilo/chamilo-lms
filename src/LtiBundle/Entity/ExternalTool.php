<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ExternalTool.
 *
 * @package Chamilo\LtiBundle\Entity
 *
 * @ORM\Table(name="lti_external_tool")
 * @ORM\Entity()
 */
class ExternalTool
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
     * @ORM\Column(name="consumer_key", type="string", nullable=true)
     */
    private $consumerKey = '';
    /**
     * @var string
     *
     * @ORM\Column(name="shared_secret", type="string", nullable=true)
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
     * @ORM\Column(name="active_deep_linking", type="boolean", nullable=false, options={"default": false})
     */
    private $activeDeepLinking = false;
    /**
     * @var string|null
     *
     * @ORM\Column(name="privacy", type="text", nullable=true, options={"default": null})
     */
    private $privacy = null;
    /**
     * @var Course|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    private $course = null;
    /**
     * @var GradebookEvaluation|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookEvaluation")
     * @ORM\JoinColumn(name="gradebook_eval_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $gradebookEval = null;
    /**
     * @var ExternalTool|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\LtiBundle\Entity\ExternalTool", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\LtiBundle\Entity\ExternalTool", mappedBy="parent")
     */
    private $children;

    /**
     * ExternalTool constructor.
     */
    public function __construct()
    {
        $this->description = null;
        $this->customParams = null;
        $this->activeDeepLinking = false;
        $this->course = null;
        $this->gradebookEval = null;
        $this->privacy = null;
        $this->consumerKey = null;
        $this->sharedSecret = null;
        $this->parent = null;
        $this->children = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id = 0;
    }

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return ExternalTool
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return ExternalTool
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
     *
     * @return ExternalTool
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
     *
     * @return ExternalTool
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
     *
     * @return ExternalTool
     */
    public function setSharedSecret($sharedSecret)
    {
        $this->sharedSecret = $sharedSecret;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCustomParams()
    {
        return $this->customParams;
    }

    /**
     * @param string|null $customParams
     *
     * @return ExternalTool
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
        return $this->course === null;
    }

    /**
     * @param array $params
     *
     * @return string|null
     */
    public function encodeCustomParams(array $params)
    {
        if (empty($params)) {
            return null;
        }
        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = "$key=$value";
        }

        return implode("\n", $pairs);
    }

    /**
     * @return array
     */
    public function parseCustomParams()
    {
        if (empty($this->customParams)) {
            return [];
        }
        $params = [];
        $strings = explode("\n", $this->customParams);
        foreach ($strings as $string) {
            if (empty($string)) {
                continue;
            }
            $pairs = explode('=', $string, 2);
            $key = self::parseCustomKey($pairs[0]);
            $value = $pairs[1];
            $params['custom_'.$key] = $value;
        }

        return $params;
    }

    /**
     * Get activeDeepLinking.
     *
     * @return bool
     */
    public function isActiveDeepLinking()
    {
        return $this->activeDeepLinking;
    }

    /**
     * Set activeDeepLinking.
     *
     * @param bool $activeDeepLinking
     *
     * @return ExternalTool
     */
    public function setActiveDeepLinking($activeDeepLinking)
    {
        $this->activeDeepLinking = $activeDeepLinking;

        return $this;
    }

    /**
     * Get course.
     *
     * @return Course|null
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Set course.
     *
     * @param Course|null $course
     *
     * @return ExternalTool
     */
    public function setCourse(Course $course = null)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get gradebookEval.
     *
     * @return GradebookEvaluation|null
     */
    public function getGradebookEval()
    {
        return $this->gradebookEval;
    }

    /**
     * Set gradebookEval.
     *
     * @param GradebookEvaluation|null $gradebookEval
     *
     * @return ExternalTool
     */
    public function setGradebookEval($gradebookEval)
    {
        $this->gradebookEval = $gradebookEval;

        return $this;
    }

    /**
     * Get privacy.
     *
     * @return string|null
     */
    public function getPrivacy()
    {
        return $this->privacy;
    }

    /**
     * Set privacy.
     *
     * @param bool $shareName
     * @param bool $shareEmail
     * @param bool $sharePicture
     *
     * @return ExternalTool
     */
    public function setPrivacy($shareName = false, $shareEmail = false, $sharePicture = false)
    {
        $this->privacy = serialize(
            [
                'share_name' => $shareName,
                'share_email' => $shareEmail,
                'share_picture' => $sharePicture,
            ]
        );

        return $this;
    }

    /**
     * @return bool
     */
    public function isSharingName()
    {
        $unserialize = $this->unserializePrivacy();

        return (bool) $unserialize['share_name'];
    }

    /**
     * @return mixed
     */
    public function unserializePrivacy()
    {
        return unserialize($this->privacy);
    }

    /**
     * @return bool
     */
    public function isSharingEmail()
    {
        $unserialize = $this->unserializePrivacy();

        return (bool) $unserialize['share_email'];
    }

    /**
     * @return bool
     */
    public function isSharingPicture()
    {
        $unserialize = $this->unserializePrivacy();

        return (bool) $unserialize['share_picture'];
    }

    /**
     * @return ExternalTool|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param ExternalTool $parent
     *
     * @return ExternalTool
     */
    public function setParent(ExternalTool $parent)
    {
        $this->parent = $parent;
        $this->sharedSecret = $parent->getSharedSecret();
        $this->consumerKey = $parent->getConsumerKey();
        $this->privacy = $parent->getPrivacy();

        return $this;
    }

    /**
     * Map the key from custom param.
     *
     * @param string $key
     *
     * @return string
     */
    private static function parseCustomKey($key)
    {
        $newKey = '';
        $key = strtolower($key);
        $split = str_split($key);
        foreach ($split as $char) {
            if (
                ($char >= 'a' && $char <= 'z') || ($char >= '0' && $char <= '9')
            ) {
                $newKey .= $char;
                continue;
            }
            $newKey .= '_';
        }

        return $newKey;
    }
}
