<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\Doctrine\Mapping;

use Xabbuh\XApi\Model\Context as ContextModel;
use Xabbuh\XApi\Model\ContextActivities;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementReference;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class Context
{
    public $identifier;

    /**
     * @var string|null
     */
    public $registration;

    /**
     * @var StatementObject|null
     */
    public $instructor;

    /**
     * @var StatementObject|null
     */
    public $team;

    /**
     * @var bool|null
     */
    public $hasContextActivities;

    /**
     * @var StatementObject[]|null
     */
    public $parentActivities;

    /**
     * @var StatementObject[]|null
     */
    public $groupingActivities;

    /**
     * @var StatementObject[]|null
     */
    public $categoryActivities;

    /**
     * @var StatementObject[]|null
     */
    public $otherActivities;

    /**
     * @var string|null
     */
    public $revision;

    /**
     * @var string|null
     */
    public $platform;

    /**
     * @var string|null
     */
    public $language;

    /**
     * @var string|null
     */
    public $statement;

    /**
     * @var Extensions|null
     */
    public $extensions;

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
