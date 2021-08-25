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

use Xabbuh\XApi\Model\Account;
use Xabbuh\XApi\Model\Actor as ActorModel;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\Group;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class Actor
{
    public $identifier;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string|null
     */
    public $mbox;

    /**
     * @var string|null
     */
    public $mboxSha1Sum;

    /**
     * @var string|null
     */
    public $openId;

    /**
     * @var string|null
     */
    public $accountName;

    /**
     * @var string|null
     */
    public $accountHomePage;

    /**
     * @var string|null
     */
    public $name;

    /**
     * @var Actor[]|null
     */
    public $members;

    public static function fromModel(ActorModel $model)
    {
        $inverseFunctionalIdentifier = $model->getInverseFunctionalIdentifier();

        $actor = new self();
        $actor->mboxSha1Sum = $inverseFunctionalIdentifier->getMboxSha1Sum();
        $actor->openId = $inverseFunctionalIdentifier->getOpenId();

        if (null !== $mbox = $inverseFunctionalIdentifier->getMbox()) {
            $actor->mbox = $mbox->getValue();
        }

        if (null !== $account = $inverseFunctionalIdentifier->getAccount()) {
            $actor->accountName = $account->getName();
            $actor->accountHomePage = $account->getHomePage()->getValue();
        }

        if ($model instanceof Group) {
            $actor->type = 'group';
            $actor->members = array();

            foreach ($model->getMembers() as $agent) {
                $actor->members[] = Actor::fromModel($agent);
            }
        } else {
            $actor->type = 'agent';
        }

        return $actor;
    }

    public function getModel()
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

        if ('group' === $this->type) {
            $members = array();

            foreach ($this->members as $agent) {
                $members[] = $agent->getModel();
            }

            return new Group($inverseFunctionalIdentifier, $this->name, $members);
        }

        return new Agent($inverseFunctionalIdentifier, $this->name);
    }
}
