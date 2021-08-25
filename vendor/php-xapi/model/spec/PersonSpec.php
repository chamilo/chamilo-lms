<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Xabbuh\XApi\Model;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\Account;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;

class PersonSpec extends ObjectBehavior
{
    function it_can_be_built_from_an_array_of_agents()
    {
        $agents = array(
            new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com'))),
            new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com'))),
        );

        $this->beConstructedThrough('createFromAgents', array($agents));

        $this->shouldHaveType('Xabbuh\XApi\Model\Person');
    }

    function it_has_mboxes_from_agents()
    {
        $mbox = IRI::fromString('mailto:conformancetest@tincanapi.com');
        $mbox2 = IRI::fromString('mailto:conformancetest2@tincanapi.com');
        $agents = array(
            new Agent(InverseFunctionalIdentifier::withMbox($mbox)),
            new Agent(InverseFunctionalIdentifier::withMbox($mbox2)),
        );

        $this->beConstructedThrough('createFromAgents', array($agents));

        $this->getMboxes()->shouldReturn(array(
            $mbox,
            $mbox2,
        ));
    }

    function it_has_mbox_sha_1_sums_from_agents()
    {
        $sha1Sum = 'sha1Sum';
        $sha1Sum2 = 'sha1Sum2';
        $agents = array(
            new Agent(InverseFunctionalIdentifier::withMboxSha1Sum($sha1Sum)),
            new Agent(InverseFunctionalIdentifier::withMboxSha1Sum($sha1Sum2)),
        );

        $this->beConstructedThrough('createFromAgents', array($agents));

        $this->getMboxSha1Sums()->shouldReturn(array(
            $sha1Sum,
            $sha1Sum2,
        ));
    }

    function it_has_open_ids_from_agents()
    {
        $openId = 'openId';
        $openId2 = 'openId2';
        $agents = array(
            new Agent(InverseFunctionalIdentifier::withOpenId($openId)),
            new Agent(InverseFunctionalIdentifier::withOpenId($openId2)),
        );

        $this->beConstructedThrough('createFromAgents', array($agents));

        $this->getOpenIds()->shouldReturn(array(
            $openId,
            $openId2,
        ));
    }

    function it_has_accounts_from_agents()
    {
        $account = new Account('test', IRL::fromString('http://example.com'));
        $account2 = new Account('test2', IRL::fromString('http://example.com'));
        $agents = array(
            new Agent(InverseFunctionalIdentifier::withAccount($account)),
            new Agent(InverseFunctionalIdentifier::withAccount($account2)),
        );

        $this->beConstructedThrough('createFromAgents', array($agents));

        $this->getAccounts()->shouldReturn(array(
            $account,
            $account2,
        ));
    }

    function it_has_names_from_agents()
    {
        $name = 'name';
        $name2 = 'name2';
        $agents = array(
            new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')), $name),
            new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')), $name2),
        );

        $this->beConstructedThrough('createFromAgents', array($agents));

        $this->getNames()->shouldReturn(array(
            $name,
            $name2,
        ));
    }
}
