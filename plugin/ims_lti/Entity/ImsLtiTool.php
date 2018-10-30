<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\ImsLti;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
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
     * @var bool
     *
     * @ORM\Column(name="active_deep_linking", type="boolean", nullable=false, options={"default": false})
     */
    private $activeDeepLinking = false;

    /**
     * @var null|string
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
     * ImsLtiTool constructor.
     */
    public function __construct()
    {
        $this->description = null;
        $this->customParams = null;
        $this->isGlobal = false;
        $this->activeDeepLinking = false;
        $this->course = null;
        $this->gradebookEval =null;
        $this->privacy = null;
    }

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

    /**
     * Set activeDeepLinking.
     *
     * @param bool $activeDeepLinking
     *
     * @return ImsLtiTool
     */
    public function setActiveDeepLinking($activeDeepLinking)
    {
        $this->activeDeepLinking = $activeDeepLinking;

        return $this;
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
     * @return ImsLtiTool
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
     * @return ImsLtiTool
     */
    public function setGradebookEval($gradebookEval)
    {
        $this->gradebookEval = $gradebookEval;

        return $this;
    }

    /**
     * Get privacy.
     *
     * @return null|string
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
     * @return ImsLtiTool
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
     * @return mixed
     */
    public function unserializePrivacy()
    {
        return unserialize($this->privacy);
    }
}
