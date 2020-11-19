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
use Xabbuh\XApi\Model\Context as ContextModel;
use Xabbuh\XApi\Model\ContextActivities;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementReference;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * @ORM\Table(name="xapi_context")
 * @ORM\Entity()
 */
class Context
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    public $identifier;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $registration;

    /**
     * @var StatementObject|null
     *
     * @ORM\OneToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\StatementObject", cascade={"ALL"})
     * @ORM\JoinColumn(referencedColumnName="identifier")
     */
    public $instructor;

    /**
     * @var StatementObject|null
     *
     * @ORM\OneToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\StatementObject", cascade={"ALL"})
     * @ORM\JoinColumn(referencedColumnName="identifier")
     */
    public $team;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="hasContextActivities", type="boolean", nullable=true)
     */
    public $hasContextActivities;

    /**
     * @var StatementObject[]|null
     *
     * @ORM\OneToMany(targetEntity="Chamilo\PluginBundle\Entity\XApi\StatementObject", mappedBy="parentContext", cascade={"ALL"})
     */
    public $parentActivities;

    /**
     * @var StatementObject[]|null
     *
     * @ORM\OneToMany(targetEntity="Chamilo\PluginBundle\Entity\XApi\StatementObject", mappedBy="groupingContext", cascade={"ALL"})
     */
    public $groupingActivities;

    /**
     * @var StatementObject[]|null
     *
     * @ORM\OneToMany(targetEntity="Chamilo\PluginBundle\Entity\XApi\StatementObject", mappedBy="categoryContext", cascade={"ALL"})
     */
    public $categoryActivities;

    /**
     * @var StatementObject[]|null
     *
     * @ORM\OneToMany(targetEntity="Chamilo\PluginBundle\Entity\XApi\StatementObject", mappedBy="otherContext", cascade={"ALL"})
     */
    public $otherActivities;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $revision;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $platform;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $language;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $statement;

    /**
     * @var Extensions|null
     *
     * @ORM\OneToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\Extensions", cascade={"ALL"})
     * @ORM\JoinColumn(referencedColumnName="identifier")
     */
    public $extensions;

    /**
     * @param \Xabbuh\XApi\Model\Context $model
     *
     * @return \Chamilo\PluginBundle\Entity\XApi\Context
     */
    public static function fromModel(ContextModel $model)
    {
        $context = new self();
        $context->registration = $model->getRegistration();
        $context->revision = $model->getRevision();
        $context->platform = $model->getPlatform();
        $context->language = $model->getLanguage();

        if (null !== $instructor = $model->getInstructor()) {
            $context->instructor = StatementObject::fromModel($instructor);
        }

        if (null !== $team = $model->getTeam()) {
            $context->team = StatementObject::fromModel($team);
        }

        if (null !== $contextActivities = $model->getContextActivities()) {
            $context->hasContextActivities = true;

            if (null !== $parentActivities = $contextActivities->getParentActivities()) {
                $context->parentActivities = array();

                foreach ($parentActivities as $parentActivity) {
                    $activity = StatementObject::fromModel($parentActivity);
                    $activity->parentContext = $context;
                    $context->parentActivities[] = $activity;
                }
            }

            if (null !== $groupingActivities = $contextActivities->getGroupingActivities()) {
                $context->groupingActivities = array();

                foreach ($groupingActivities as $groupingActivity) {
                    $activity = StatementObject::fromModel($groupingActivity);
                    $activity->groupingContext = $context;
                    $context->groupingActivities[] = $activity;
                }
            }

            if (null !== $categoryActivities = $contextActivities->getCategoryActivities()) {
                $context->categoryActivities = array();

                foreach ($categoryActivities as $categoryActivity) {
                    $activity = StatementObject::fromModel($categoryActivity);
                    $activity->categoryContext = $context;
                    $context->categoryActivities[] = $activity;
                }
            }

            if (null !== $otherActivities = $contextActivities->getOtherActivities()) {
                $context->otherActivities = array();

                foreach ($otherActivities as $otherActivity) {
                    $activity = StatementObject::fromModel($otherActivity);
                    $activity->otherContext = $context;
                    $context->otherActivities[] = $activity;
                }
            }
        } else {
            $context->hasContextActivities = false;
        }

        if (null !== $statementReference = $model->getStatement()) {
            $context->statement = $statementReference->getStatementId()->getValue();
        }

        if (null !== $contextExtensions = $model->getExtensions()) {
            $context->extensions = Extensions::fromModel($contextExtensions);
        }

        return $context;
    }

    /**
     * @return \Xabbuh\XApi\Model\Context
     */
    public function getModel()
    {
        $context = new ContextModel();
        $context = $context->withRegistration($this->registration);
        $context = $context->withRevision($this->revision);
        $context = $context->withPlatform($this->platform);
        $context = $context->withLanguage($this->language);

        if (null !== $this->instructor) {
            $context = $context->withInstructor($this->instructor->getModel());
        }

        if (null !== $this->team) {
            $context = $context->withTeam($this->team->getModel());
        }

        if ($this->hasContextActivities) {
            $contextActivities = new ContextActivities();

            if (null !== $this->parentActivities) {
                foreach ($this->parentActivities as $contextParentActivity) {
                    $contextActivities = $contextActivities->withAddedParentActivity($contextParentActivity->getModel());
                }
            }

            if (null !== $this->groupingActivities) {
                foreach ($this->groupingActivities as $contextGroupingActivity) {
                    $contextActivities = $contextActivities->withAddedGroupingActivity($contextGroupingActivity->getModel());
                }
            }

            if (null !== $this->categoryActivities) {
                foreach ($this->categoryActivities as $contextCategoryActivity) {
                    $contextActivities = $contextActivities->withAddedCategoryActivity($contextCategoryActivity->getModel());
                }
            }

            if (null !== $this->otherActivities) {
                foreach ($this->otherActivities as $contextOtherActivity) {
                    $contextActivities = $contextActivities->withAddedOtherActivity($contextOtherActivity->getModel());
                }
            }

            $context = $context->withContextActivities($contextActivities);
        }

        if (null !== $this->statement) {
            $context = $context->withStatement(new StatementReference(StatementId::fromString($this->statement)));
        }

        if (null !== $this->extensions) {
            $context = $context->withExtensions($this->extensions->getModel());
        }

        return $context;
    }
}
