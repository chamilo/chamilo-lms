<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ExternalTool.
 *
 * @ORM\Table(name="lti_external_tool")
 * @ORM\Entity()
 */
class ExternalTool
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="name", type="string")
     */
    protected string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="launch_url", type="string")
     */
    protected string $launchUrl;

    /**
     * @ORM\Column(name="consumer_key", type="string", nullable=true)
     */
    protected ?string $consumerKey;

    /**
     * @ORM\Column(name="shared_secret", type="string", nullable=true)
     */
    protected ?string $sharedSecret;

    /**
     * @ORM\Column(name="custom_params", type="text", nullable=true)
     */
    protected ?string $customParams = null;

    /**
     * @ORM\Column(name="active_deep_linking", type="boolean", nullable=false, options={"default": false})
     */
    protected bool $activeDeepLinking;

    /**
     * @ORM\Column(name="privacy", type="text", nullable=true, options={"default": null})
     */
    protected ?string $privacy = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected ?Course $course = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookEvaluation")
     * @ORM\JoinColumn(name="gradebook_eval_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected ?GradebookEvaluation $gradebookEval = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\LtiBundle\Entity\ExternalTool", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected ?ExternalTool $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\LtiBundle\Entity\ExternalTool", mappedBy="parent")
     */
    protected Collection $children;

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

    public function getId(): int
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
     *
     * @return ExternalTool
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
     * @return null|string
     */
    public function getCustomParams()
    {
        return $this->customParams;
    }

    /**
     * @param null|string $customParams
     *
     * @return ExternalTool
     */
    public function setCustomParams($customParams)
    {
        $this->customParams = $customParams;

        return $this;
    }

    public function isGlobal(): bool
    {
        return null === $this->course;
    }

    /**
     * @return null|string
     */
    public function encodeCustomParams(array $params)
    {
        if (empty($params)) {
            return null;
        }
        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = "{$key}={$value}";
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

    public function isActiveDeepLinking(): bool
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
     * @return null|Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Set course.
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
     * @return null|GradebookEvaluation
     */
    public function getGradebookEval()
    {
        return $this->gradebookEval;
    }

    /**
     * Set gradebookEval.
     *
     * @param null|GradebookEvaluation $gradebookEval
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

    public function isSharingName(): bool
    {
        $unserialize = $this->unserializePrivacy();

        if (!$unserialize) {
            return false;
        }

        return (bool) $unserialize['share_name'];
    }

    public function unserializePrivacy()
    {
        return unserialize((string) $this->privacy);
    }

    public function isSharingEmail(): bool
    {
        $unserialize = $this->unserializePrivacy();

        if (!$unserialize) {
            return false;
        }

        return (bool) $unserialize['share_email'];
    }

    public function isSharingPicture(): bool
    {
        $unserialize = $this->unserializePrivacy();

        if (!$unserialize) {
            return false;
        }

        return (bool) $unserialize['share_picture'];
    }

    /**
     * @return null|ExternalTool
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return ExternalTool
     */
    public function setParent(self $parent)
    {
        $this->parent = $parent;
        $this->sharedSecret = $parent->getSharedSecret();
        $this->consumerKey = $parent->getConsumerKey();
        $this->privacy = $parent->getPrivacy();

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @return ExternalTool
     */
    public function setChildren(Collection $children): self
    {
        $this->children = $children;

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
