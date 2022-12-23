<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\ImsLti;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ImsLtiTool.
 *
 * @ORM\Table(name="plugin_ims_lti_tool")
 * @ORM\Entity()
 */
class ImsLtiTool
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="public_key", type="text", nullable=true)
     */
    public $publicKey;
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
     * @var Session|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    private $session = null;
    /**
     * @var GradebookEvaluation|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookEvaluation")
     * @ORM\JoinColumn(name="gradebook_eval_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $gradebookEval = null;
    /**
     * @var ImsLtiTool|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool", mappedBy="parent")
     */
    private $children;
    /**
     * @var string
     *
     * @ORM\Column(name="client_id", type="string", nullable=true)
     */
    private $clientId;
    /**
     * @var string|null
     *
     * @ORM\Column(name="login_url", type="string", nullable=true)
     */
    private $loginUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="redirect_url", type="string", nullable=true)
     */
    private $redirectUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="jwks_url", type="string", nullable=true)
     */
    private $jwksUrl;

    /**
     * @var array
     *
     * @ORM\Column(name="advantage_services", type="json", nullable=true)
     */
    private $advantageServices;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\PluginBundle\Entity\ImsLti\LineItem", mappedBy="tool")
     */
    private $lineItems;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", options={"default": "lti1p1"})
     */
    private $version;
    /**
     * @var array
     *
     * @ORM\Column(name="launch_presentation", type="json")
     */
    private $launchPresentation;

    /**
     * @var array
     *
     * @ORM\Column(name="replacement_params", type="json")
     */
    private $replacementParams;

    /**
     * ImsLtiTool constructor.
     */
    public function __construct()
    {
        $this->description = null;
        $this->customParams = null;
        $this->activeDeepLinking = false;
        $this->course = null;
        $this->gradebookEval = null;
        $this->privacy = null;
        $this->children = new ArrayCollection();
        $this->consumerKey = null;
        $this->sharedSecret = null;
        $this->lineItems = new ArrayCollection();
        $this->version = \ImsLti::V_1P3;
        $this->launchPresentation = [
            'document_target' => 'iframe',
        ];
        $this->replacementParams = [];
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
     *
     * @return ImsLtiTool
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
     *
     * @return ImsLtiTool
     */
    public function setLaunchUrl($launchUrl)
    {
        $this->launchUrl = $launchUrl;

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
        return $this->course === null;
    }

    /**
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
    public function getCustomParamsAsArray()
    {
        $params = [];
        $lines = explode("\n", $this->customParams);
        $lines = array_filter($lines);

        foreach ($lines as $line) {
            list($key, $value) = explode('=', $line, 2);

            $key = self::filterSpecialChars($key);
            $value = self::filterSpaces($value);

            $params[$key] = $value;
        }

        return $params;
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
            $key = self::filterSpecialChars($pairs[0]);
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
     * @return ImsLtiTool
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
     * @return ImsLtiTool
     */
    public function setCourse(Course $course = null)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get session.
     *
     * @return Session|null
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set session.
     *
     * @param Session|null $course
     *
     * @return ImsLtiTool
     */
    public function setSession(Session $session = null)
    {
        $this->session = $session;

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
        return \UnserializeApi::unserialize('not_allowed_classes', $this->privacy);
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
     * @return ImsLtiTool|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return ImsLtiTool
     */
    public function setParent(ImsLtiTool $parent)
    {
        $this->parent = $parent;

        $this->sharedSecret = $parent->getSharedSecret();
        $this->consumerKey = $parent->getConsumerKey();
        $this->privacy = $parent->getPrivacy();

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
     * @return ImsLtiTool
     */
    public function setSharedSecret($sharedSecret)
    {
        $this->sharedSecret = $sharedSecret;

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
     * @return ImsLtiTool
     */
    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;

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
     * Get loginUrl.
     *
     * @return string|null
     */
    public function getLoginUrl()
    {
        return $this->loginUrl;
    }

    /**
     * Set loginUrl.
     *
     * @param string|null $loginUrl
     *
     * @return ImsLtiTool
     */
    public function setLoginUrl($loginUrl)
    {
        $this->loginUrl = $loginUrl;

        return $this;
    }

    /**
     * Get redirectUlr.
     *
     * @return string|null
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * Set redirectUrl.
     *
     * @param string|null $redirectUrl
     *
     * @return ImsLtiTool
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    /**
     * Get jwksUrl.
     *
     * @return string|null
     */
    public function getJwksUrl()
    {
        return $this->jwksUrl;
    }

    /**
     * Set jwksUrl.
     *
     * @param string|null $jwksUrl
     *
     * @return ImsLtiTool
     */
    public function setJwksUrl($jwksUrl)
    {
        $this->jwksUrl = $jwksUrl;

        return $this;
    }

    /**
     * Get clientId.
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Set clientId.
     *
     * @param string $clientId
     *
     * @return ImsLtiTool
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * Get advantageServices.
     *
     * @return array
     */
    public function getAdvantageServices()
    {
        if (empty($this->advantageServices)) {
            $this->advantageServices = [];
        }

        return array_merge(
            [
                'ags' => \LtiAssignmentGradesService::AGS_NONE,
                'nrps' => \LtiNamesRoleProvisioningService::NRPS_NONE,
            ],
            $this->advantageServices
        );
    }

    /**
     * Set advantageServices.
     *
     * @param array $advantageServices
     *
     * @return ImsLtiTool
     */
    public function setAdvantageServices($advantageServices)
    {
        $this->advantageServices = $advantageServices;

        return $this;
    }

    /**
     * Add LineItem to lineItems.
     *
     * @return $this
     */
    public function addLineItem(LineItem $lineItem)
    {
        $lineItem->setTool($this);

        $this->lineItems[] = $lineItem;

        return $this;
    }

    /**
     * @param int    $resourceLinkId
     * @param int    $resourceId
     * @param string $tag
     * @param int    $limit
     * @param int    $page
     *
     * @return ArrayCollection
     */
    public function getLineItems($resourceLinkId = 0, $resourceId = 0, $tag = '', $limit = 0, $page = 1)
    {
        $criteria = Criteria::create();

        if ($resourceLinkId) {
            $criteria->andWhere(Criteria::expr()->eq('tool', $resourceId));
        }

        if ($resourceId) {
            $criteria->andWhere(Criteria::expr()->eq('tool', $resourceId));
        }

        if (!empty($tag)) {
            $criteria->andWhere(Criteria::expr()->eq('tag', $tag));
        }

        $limit = (int) $limit;
        $page = (int) $page;

        if ($limit > 0) {
            $criteria->setMaxResults($limit);

            if ($page > 0) {
                $criteria->setFirstResult($page * $limit);
            }
        }

        return $this->lineItems->matching($criteria);
    }

    /**
     * Set lineItems.
     *
     * @return $this
     */
    public function setLineItems(ArrayCollection $lineItems)
    {
        $this->lineItems = $lineItems;

        return $this;
    }

    /**
     * Get version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set version.
     *
     * @param string $version
     *
     * @return ImsLtiTool
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersionName()
    {
        if (\ImsLti::V_1P1 === $this->version) {
            return 'LTI 1.0 / 1.1';
        }

        return 'LTI 1.3';
    }

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param string $target
     *
     * @return $this
     */
    public function setDocumenTarget($target)
    {
        $this->launchPresentation['document_target'] = in_array($target, ['iframe', 'window']) ? $target : 'iframe';

        return $this;
    }

    /**
     * @return string
     */
    public function getDocumentTarget()
    {
        return $this->launchPresentation['document_target'] ?: 'iframe';
    }

    /**
     * @return array
     */
    public function getLaunchPresentation()
    {
        return $this->launchPresentation;
    }

    /**
     * @param string $replacement
     *
     * @return ImsLtiTool
     */
    public function setReplacementForUserId($replacement)
    {
        $this->replacementParams['user_id'] = $replacement;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getReplacementForUserId()
    {
        if (!empty($this->replacementParams['user_id'])) {
            return $this->replacementParams['user_id'];
        }

        return null;
    }

    /**
     * @return ArrayCollection
     */
    public function getChildrenInCourses(array $coursesId)
    {
        return $this->children->filter(
            function (ImsLtiTool $child) use ($coursesId) {
                return in_array($child->getCourse()->getId(), $coursesId);
            }
        );
    }

    /**
     * Map the key from custom param.
     *
     * @param string $key
     *
     * @return string
     */
    private static function filterSpecialChars($key)
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

    /**
     * @param string $value
     *
     * @return string
     */
    private static function filterSpaces($value)
    {
        $newValue = preg_replace('/\s+/', ' ', $value);

        return trim($newValue);
    }
}
