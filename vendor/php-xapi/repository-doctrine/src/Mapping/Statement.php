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

use Xabbuh\XApi\Model\Statement as StatementModel;
use Xabbuh\XApi\Model\StatementId;

/**
 * A {@link Statement} mapped to a storage backend.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class Statement
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var StatementObject
     */
    public $actor;

    /**
     * @var Verb
     */
    public $verb;

    /**
     * @var StatementObject
     */
    public $object;

    /**
     * @var Result
     */
    public $result;

    /**
     * @var StatementObject
     */
    public $authority;

    /**
     * @var int
     */
    public $created;

    /**
     * @var int
     */
    public $stored;

    /**
     * @var Context
     */
    public $context;

    /**
     * @var bool
     */
    public $hasAttachments;

    /**
     * @var Attachment[]|null
     */
    public $attachments;

    public static function fromModel(StatementModel $model)
    {
        $statement = new self();
        $statement->id = $model->getId()->getValue();
        $statement->actor = StatementObject::fromModel($model->getActor());
        $statement->verb = Verb::fromModel($model->getVerb());
        $statement->object = StatementObject::fromModel($model->getObject());

        if (null !== $model->getCreated()) {
            $statement->created = $model->getCreated()->getTimestamp();
        }

        if (null !== $result = $model->getResult()) {
            $statement->result = Result::fromModel($result);
        }

        if (null !== $authority = $model->getAuthority()) {
            $statement->authority = StatementObject::fromModel($authority);
        }

        if (null !== $context = $model->getContext()) {
            $statement->context = Context::fromModel($context);
        }

        if (null !== $attachments = $model->getAttachments()) {
            $statement->hasAttachments = true;
            $statement->attachments = array();

            foreach ($attachments as $attachment) {
                $mappedAttachment = Attachment::fromModel($attachment);
                $mappedAttachment->statement = $statement;
                $statement->attachments[] = $mappedAttachment;
            }
        } else {
            $statement->hasAttachments = false;
        }

        return $statement;
    }

    public function getModel()
    {
        $result = null;
        $authority = null;
        $created = null;
        $stored = null;
        $context = null;
        $attachments = null;

        if (null !== $this->result) {
            $result = $this->result->getModel();
        }

        if (null !== $this->authority) {
            $authority = $this->authority->getModel();
        }

        if (null !== $this->created) {
            $created = new \DateTime('@'.$this->created);
        }

        if (null !== $this->stored) {
            $stored = new \DateTime('@'.$this->stored);
        }

        if (null !== $this->context) {
            $context = $this->context->getModel();
        }

        if ($this->hasAttachments) {
            $attachments = array();

            foreach ($this->attachments as $attachment) {
                $attachments[] = $attachment->getModel();
            }
        }

        return new StatementModel(
            StatementId::fromString($this->id),
            $this->actor->getModel(),
            $this->verb->getModel(),
            $this->object->getModel(),
            $result,
            $authority,
            $created,
            $stored,
            $context,
            $attachments
        );
    }
}
