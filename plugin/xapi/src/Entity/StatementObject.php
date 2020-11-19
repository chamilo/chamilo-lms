<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chamilo\PluginBundle\Entity\XApi;

use Doctrine\ORM\Mapping as ORM;
use Xabbuh\XApi\Model\Account;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Actor as ActorModel;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\Group;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Object as ObjectModel;
use Xabbuh\XApi\Model\StatementObject as StatementObjectModel;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementReference;
use Xabbuh\XApi\Model\SubStatement;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * @ORM\Table(name="xapi_object")
 * @ORM\Entity()
 */
class StatementObject
{
    const TYPE_ACTIVITY = 'activity';
    const TYPE_AGENT = 'agent';
    const TYPE_GROUP = 'group';
    const TYPE_STATEMENT_REFERENCE = 'statement_reference';
    const TYPE_SUB_STATEMENT = 'sub_statement';

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    public $identifier;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $type;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $activityId;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $hasActivityDefinition;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $hasActivityName;

    /**
     * @var array|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    public $activityName;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $hasActivityDescription;

    /**
     * @var array|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    public $activityDescription;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $activityType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $activityMoreInfo;

    /**
     * @var Extensions|null
     *
     * @ORM\OneToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\Extensions", cascade={"all"})
     * @ORM\JoinColumn(referencedColumnName="identifier")
     */
    public $activityExtensions;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $mbox;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $mboxSha1Sum;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $openId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $accountName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $accountHomePage;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $name;

    /**
     * @var StatementObject[]|null
     *
     * @ORM\OneToMany(targetEntity="Chamilo\PluginBundle\Entity\XApi\StatementObject", mappedBy="group")
     */
    public $members;

    /**
     * @var StatementObject|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\StatementObject", inversedBy="members")
     * @ORM\JoinColumn(referencedColumnName="identifier")
     */
    public $group;

    /**
     * @var string|null
     *
     * @ORM\Column(name="referenced_statement_id", type="string", nullable=true)
     */
    public $referencedStatementId;

    /**
     * @var StatementObject|null
     *
     * @ORM\OneToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\StatementObject", cascade={"ALL"})
     * @ORM\JoinColumn(referencedColumnName="identifier")
     */
    public $actor;

    /**
     * @var Verb|null
     *
     * @ORM\OneToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\Verb", cascade={"ALL"})
     * @ORM\JoinColumn(referencedColumnName="identifier")
     */
    public $verb;

    /**
     * @var StatementObject|null
     *
     * @ORM\OneToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\StatementObject", cascade={"ALL"})
     * @ORM\JoinColumn(referencedColumnName="identifier")
     */
    public $object;

    /**
     * @var Result|null
     */
    public $result;

    /**
     * @var Context|null
     */
    public $context;

    /**
     * @var Statement|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\Context", inversedBy="parentActivities")
     * @ORM\JoinColumn(referencedColumnName="identifier")
     */
    public $parentContext;

    /**
     * @var Statement|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\Context", inversedBy="groupingActivities")
     * @ORM\JoinColumn(referencedColumnName="identifier")
     */
    public $groupingContext;

    /**
     * @var Statement|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\Context", inversedBy="categoryActivities")
     * @ORM\JoinColumn(referencedColumnName="identifier")
     */
    public $categoryContext;

    /**
     * @var Statement|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\Context", inversedBy="otherActivities")
     * @ORM\JoinColumn(referencedColumnName="identifier")
     */
    public $otherContext;

    /**
     * @param $model
     *
     * @return \Chamilo\PluginBundle\Entity\XApi\StatementObject
     */
    public static function fromModel($model)
    {
        if (!$model instanceof ObjectModel && !$model instanceof StatementObjectModel) {
            throw new \InvalidArgumentException(sprintf('Expected a statement object but got %s', is_object($model) ? get_class($model) : gettype($model)));
        }

        if ($model instanceof ActorModel) {
            return self::fromActor($model);
        }

        if ($model instanceof StatementReference) {
            $object = new self();
            $object->type = self::TYPE_STATEMENT_REFERENCE;
            $object->referencedStatementId = $model->getStatementId()->getValue();

            return $object;
        }

        if ($model instanceof SubStatement) {
            return self::fromSubStatement($model);
        }

        return self::fromActivity($model);
    }

    /**
     * @return \Xabbuh\XApi\Model\StatementObject
     */
    public function getModel()
    {
        if (self::TYPE_AGENT === $this->type || self::TYPE_GROUP === $this->type) {
            return $this->getActorModel();
        }

        if (self::TYPE_STATEMENT_REFERENCE === $this->type) {
            return new StatementReference(StatementId::fromString($this->referencedStatementId));
        }

        if (self::TYPE_SUB_STATEMENT === $this->type) {
            return $this->getSubStatementModel();
        }

        return $this->getActivityModel();
    }

    /**
     * @param \Xabbuh\XApi\Model\Activity $model
     *
     * @return \Chamilo\PluginBundle\Entity\XApi\StatementObject
     */
    private static function fromActivity(Activity $model)
    {
        $object = new self();
        $object->activityId = $model->getId()->getValue();

        if (null !== $definition = $model->getDefinition()) {
            $object->hasActivityDefinition = true;

            if (null !== $name = $definition->getName()) {
                $object->hasActivityName = true;
                $object->activityName = array();

                foreach ($name->languageTags() as $languageTag) {
                    $object->activityName[$languageTag] = $name[$languageTag];
                }
            } else {
                $object->hasActivityName = false;
            }

            if (null !== $description = $definition->getDescription()) {
                $object->hasActivityDescription = true;
                $object->activityDescription = array();

                foreach ($description->languageTags() as $languageTag) {
                    $object->activityDescription[$languageTag] = $description[$languageTag];
                }
            } else {
                $object->hasActivityDescription = false;
            }

            if (null !== $type = $definition->getType()) {
                $object->activityType = $type->getValue();
            }

            if (null !== $moreInfo = $definition->getMoreInfo()) {
                $object->activityMoreInfo = $moreInfo->getValue();
            }

            if (null !== $extensions = $definition->getExtensions()) {
                $object->activityExtensions = Extensions::fromModel($extensions);
            }
        } else {
            $object->hasActivityDefinition = false;
        }

        return $object;
    }

    /**
     * @param \Xabbuh\XApi\Model\Actor $model
     *
     * @return \Chamilo\PluginBundle\Entity\XApi\StatementObject
     */
    private static function fromActor(ActorModel $model)
    {
        $inverseFunctionalIdentifier = $model->getInverseFunctionalIdentifier();

        $object = new self();
        $object->mboxSha1Sum = $inverseFunctionalIdentifier->getMboxSha1Sum();
        $object->openId = $inverseFunctionalIdentifier->getOpenId();

        if (null !== $mbox = $inverseFunctionalIdentifier->getMbox()) {
            $object->mbox = $mbox->getValue();
        }

        if (null !== $account = $inverseFunctionalIdentifier->getAccount()) {
            $object->accountName = $account->getName();
            $object->accountHomePage = $account->getHomePage()->getValue();
        }

        if ($model instanceof Group) {
            $object->type = self::TYPE_GROUP;
            $object->members = array();

            foreach ($model->getMembers() as $agent) {
                $object->members[] = self::fromActor($agent);
            }
        } else {
            $object->type = self::TYPE_AGENT;
        }

        return $object;
    }

    /**
     * @param \Xabbuh\XApi\Model\SubStatement $model
     *
     * @return \Chamilo\PluginBundle\Entity\XApi\StatementObject
     */
    private static function fromSubStatement(SubStatement $model)
    {
        $object = new self();
        $object->type = self::TYPE_SUB_STATEMENT;
        $object->actor = StatementObject::fromModel($model->getActor());
        $object->verb = Verb::fromModel($model->getVerb());
        $object->object = StatementObject::fromModel($model->getObject());

        return $object;
    }

    /**
     * @return \Xabbuh\XApi\Model\Activity
     */
    private function getActivityModel()
    {
        $definition = null;
        $type = null;
        $moreInfo = null;

        if ($this->hasActivityDefinition) {
            $name = null;
            $description = null;
            $extensions = null;

            if ($this->hasActivityName) {
                $name = LanguageMap::create($this->activityName);
            }

            if ($this->hasActivityDescription) {
                $description = LanguageMap::create($this->activityDescription);
            }

            if (null !== $this->activityType) {
                $type = IRI::fromString($this->activityType);
            }

            if (null !== $this->activityMoreInfo) {
                $moreInfo = IRL::fromString($this->activityMoreInfo);
            }

            if (null !== $this->activityExtensions) {
                $extensions = $this->activityExtensions->getModel();
            }

            $definition = new Definition($name, $description, $type, $moreInfo, $extensions);
        }

        return new Activity(IRI::fromString($this->activityId), $definition);
    }

    /**
     * @return \Xabbuh\XApi\Model\Agent|\Xabbuh\XApi\Model\Group
     */
    private function getActorModel()
    {
        $inverseFunctionalIdentifier = null;

        if (null !== $this->mbox) {
            $inverseFunctionalIdentifier = InverseFunctionalIdentifier::withMbox(IRI::fromString($this->mbox));
        } elseif (null !== $this->mboxSha1Sum) {
            $inverseFunctionalIdentifier = InverseFunctionalIdentifier::withMboxSha1Sum($this->mboxSha1Sum);
        } elseif (null !== $this->openId) {
            $inverseFunctionalIdentifier = InverseFunctionalIdentifier::withOpenId($this->openId);
        } elseif (null !== $this->accountName && null !== $this->accountHomePage) {
            $inverseFunctionalIdentifier = InverseFunctionalIdentifier::withAccount(new Account($this->accountName, IRL::fromString($this->accountHomePage)));
        }

        if (self::TYPE_GROUP === $this->type) {
            $members = array();

            foreach ($this->members as $agent) {
                $members[] = $agent->getModel();
            }

            return new Group($inverseFunctionalIdentifier, $this->name, $members);
        }

        return new Agent($inverseFunctionalIdentifier, $this->name);
    }

    /**
     * @return \Xabbuh\XApi\Model\SubStatement
     */
    private function getSubStatementModel()
    {
        $result = null;
        $context = null;

        return new SubStatement(
            $this->actor->getModel(),
            $this->verb->getModel(),
            $this->object->getModel(),
            $result,
            $context
        );
    }
}
