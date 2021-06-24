<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\LtiBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceToRootInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use UnserializeApi;

/**
 * Class ExternalTool.
 *
 * @ORM\Table(name="lti_external_tool")
 * @ORM\Entity
 */
class ExternalTool extends AbstractResource implements ResourceInterface, ResourceToRootInterface
{
    public const V_1P1 = 'lti1p1';
    public const V_1P3 = 'lti1p3';

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
    protected ?string $description;

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
    protected ?string $customParams;

    /**
     * @ORM\Column(name="active_deep_linking", type="boolean", nullable=false, options={"default": false})
     */
    protected bool $activeDeepLinking;

    /**
     * @ORM\Column(name="privacy", type="text", nullable=true, options={"default": null})
     */
    protected ?string $privacy;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected ?Course $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookEvaluation")
     * @ORM\JoinColumn(name="gradebook_eval_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected ?GradebookEvaluation $gradebookEval;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\LtiBundle\Entity\ExternalTool", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected ?ExternalTool $parent;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\LtiBundle\Entity\ExternalTool", mappedBy="parent")
     */
    protected Collection $children;

    /**
     * @ORM\Column(name="client_id", type="string", nullable=true)
     */
    private ?string $clientId;
    /**
     * @ORM\Column(name="login_url", type="string", nullable=true)
     */
    private ?string $loginUrl;

    /**
     * @ORM\Column(name="redirect_url", type="string", nullable=true)
     */
    private ?string $redirectUrl;

    /**
     * @ORM\Column(name="advantage_services", type="json", nullable=true)
     */
    private ?array $advantageServices;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\LtiBundle\Entity\LineItem", mappedBy="tool")
     */
    private Collection $lineItems;

    /**
     * @ORM\Column(name="version", type="string", options={"default": "lti1p1"})
     */
    private string $version;
    /**
     * @ORM\Column(name="launch_presentation", type="json")
     */
    private array $launchPresentation;

    /**
     * @ORM\Column(name="replacement_params", type="json")
     */
    private array $replacementParams;

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
        $this->consumerKey = null;
        $this->sharedSecret = null;
        $this->lineItems = new ArrayCollection();
        $this->version = self::V_1P1;
        $this->launchPresentation = [
            'document_target' => 'iframe',
        ];
        $this->replacementParams = [];
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLaunchUrl(): string
    {
        return $this->launchUrl;
    }

    public function setLaunchUrl(string $launchUrl): static
    {
        $this->launchUrl = $launchUrl;

        return $this;
    }

    public function getCustomParams(): ?string
    {
        return $this->customParams;
    }

    public function setCustomParams(?string $customParams): static
    {
        $this->customParams = $customParams;

        return $this;
    }

    public function isGlobal(): bool
    {
        return null === $this->course;
    }

    public function encodeCustomParams(array $params): ?string
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

    public function getCustomParamsAsArray(): array
    {
        $params = [];
        $lines = explode("\n", $this->customParams);
        $lines = array_filter($lines);

        foreach ($lines as $line) {
            [$key, $value] = explode('=', $line, 2);

            $key = self::filterSpecialChars($key);
            $value = self::filterSpaces($value);

            $params[$key] = $value;
        }

        return $params;
    }

    public function parseCustomParams(): array
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

    public function isActiveDeepLinking(): bool
    {
        return $this->activeDeepLinking;
    }

    public function setActiveDeepLinking(bool $activeDeepLinking): static
    {
        $this->activeDeepLinking = $activeDeepLinking;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(Course $course = null): static
    {
        $this->course = $course;

        return $this;
    }

    public function getGradebookEval(): ?GradebookEvaluation
    {
        return $this->gradebookEval;
    }

    public function setGradebookEval(?GradebookEvaluation $gradebookEval): static
    {
        $this->gradebookEval = $gradebookEval;

        return $this;
    }

    public function getPrivacy(): ?string
    {
        return $this->privacy;
    }

    public function setPrivacy(bool $shareName = false, bool $shareEmail = false, bool $sharePicture = false): static
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

        return (bool) $unserialize['share_name'];
    }

    public function unserializePrivacy(): array
    {
        return UnserializeApi::unserialize('not_allowed_classes', $this->privacy);
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

    public function getToolParent(): ?self
    {
        return $this->parent;
    }

    public function setToolParent(self $parent): static
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

    public function setChildren(Collection $children): static
    {
        $this->children = $children;

        return $this;
    }

    public function getSharedSecret(): ?string
    {
        return $this->sharedSecret;
    }

    public function setSharedSecret(?string $sharedSecret): static
    {
        $this->sharedSecret = $sharedSecret;

        return $this;
    }

    public function getConsumerKey(): ?string
    {
        return $this->consumerKey;
    }

    public function setConsumerKey(?string $consumerKey): static
    {
        $this->consumerKey = $consumerKey;

        return $this;
    }

    public function getLoginUrl(): ?string
    {
        return $this->loginUrl;
    }

    public function setLoginUrl(?string $loginUrl): static
    {
        $this->loginUrl = $loginUrl;

        return $this;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function setRedirectUrl(?string $redirectUrl): static
    {
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(?string $clientId): static
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getAdvantageServices(): array
    {
        if (empty($this->advantageServices)) {
            $this->advantageServices = [];
        }

        return array_merge(
            [
                'ags' => LtiAssignmentGradesService::AGS_NONE,
                'nrps' => LtiNamesRoleProvisioningService::NRPS_NONE,
            ],
            $this->advantageServices
        );
    }

    public function setAdvantageServices(array $advantageServices): static
    {
        $this->advantageServices = $advantageServices;

        return $this;
    }

    public function addLineItem(LineItem $lineItem): static
    {
        $lineItem->setTool($this);

        $this->lineItems[] = $lineItem;

        return $this;
    }

    public function getLineItems(
        int $resourceLinkId = 0,
        int $resourceId = 0,
        string $tag = '',
        int $limit = 0,
        int $page = 1
    ): Collection {
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

        if ($limit > 0) {
            $criteria->setMaxResults($limit);

            if ($page > 0) {
                $criteria->setFirstResult($page * $limit);
            }
        }

        return $this->lineItems->matching($criteria);
    }

    public function setLineItems(Collection $lineItems): static
    {
        $this->lineItems = $lineItems;

        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getVersionName(): string
    {
        if (self::V_1P1 === $this->version) {
            return 'LTI 1.0 / 1.1';
        }

        return 'LTI 1.3';
    }

    public function setDocumenTarget(string $target): static
    {
        $this->launchPresentation['document_target'] = \in_array($target, ['iframe', 'window'], true) ? $target : 'iframe';

        return $this;
    }

    public function getDocumentTarget()
    {
        return $this->launchPresentation['document_target'] ?: 'iframe';
    }

    public function getLaunchPresentation(): array
    {
        return $this->launchPresentation;
    }

    public function setReplacementForUserId(string $replacement): static
    {
        $this->replacementParams['user_id'] = $replacement;

        return $this;
    }

    public function getReplacementForUserId(): ?string
    {
        if (!empty($this->replacementParams['user_id'])) {
            return $this->replacementParams['user_id'];
        }

        return null;
    }

    public function getChildrenInCourses(array $coursesId): Collection
    {
        return $this->children->filter(
            function (self $child) use ($coursesId) {
                return \in_array($child->getCourse()->getId(), $coursesId, true);
            }
        );
    }

    public function getResourceName(): string
    {
        return $this->getName();
    }

    public function setResourceName(string $name): static
    {
        return $this->setName($name);
    }

    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    private static function filterSpaces(string $value): string
    {
        $newValue = preg_replace('/\s+/', ' ', $value);

        return trim($newValue);
    }

    private static function filterSpecialChars(string $key): string
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
