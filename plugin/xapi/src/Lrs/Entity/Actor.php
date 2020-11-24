<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chamilo\PluginBundle\Entity\XApi\Lrs;

use Doctrine\ORM\Mapping as ORM;
use Xabbuh\XApi\Model\Account;
use Xabbuh\XApi\Model\Actor as ActorModel;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\Group;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * @ORM\Table(name="xapi_actor")
 * @ORM\Entity()
 */
class Actor
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
     * @var Actor[]|null
     *
     * @ORM\Column()
     */
    public $members;

    /**
     * @param \Xabbuh\XApi\Model\Actor $model
     *
     * @return \Chamilo\PluginBundle\Entity\XApi\Lrs\Actor
     */
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

    /**
     * @return \Xabbuh\XApi\Model\Agent|\Xabbuh\XApi\Model\Group
     */
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
