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
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;

class InverseFunctionalIdentifierSpec extends ObjectBehavior
{
    function it_can_be_built_with_an_mbox()
    {
        $iri = IRI::fromString('mailto:conformancetest@tincanapi.com');
        $this->beConstructedThrough(
            array(InverseFunctionalIdentifier::class, 'withMbox'),
            array($iri)
        );

        $this->getMbox()->shouldReturn($iri);
        $this->getMboxSha1Sum()->shouldReturn(null);
        $this->getOpenId()->shouldReturn(null);
        $this->getAccount()->shouldReturn(null);
    }

    function it_can_be_built_with_an_mbox_sha1_sum()
    {
        $this->beConstructedThrough(
            array(InverseFunctionalIdentifier::class, 'withMboxSha1Sum'),
            array('db77b9104b531ecbb0b967f6942549d0ba80fda1')
        );

        $this->getMbox()->shouldReturn(null);
        $this->getMboxSha1Sum()->shouldReturn('db77b9104b531ecbb0b967f6942549d0ba80fda1');
        $this->getOpenId()->shouldReturn(null);
        $this->getAccount()->shouldReturn(null);
    }

    function it_can_be_built_with_an_openid()
    {
        $this->beConstructedThrough(
            array(InverseFunctionalIdentifier::class, 'withOpenId'),
            array('http://openid.tincanapi.com')
        );

        $this->getMbox()->shouldReturn(null);
        $this->getMboxSha1Sum()->shouldReturn(null);
        $this->getOpenId()->shouldReturn('http://openid.tincanapi.com');
        $this->getAccount()->shouldReturn(null);
    }

    function it_can_be_built_with_an_account()
    {
        $account = new Account('test', IRL::fromString('https://tincanapi.com'));
        $this->beConstructedThrough(
            array(InverseFunctionalIdentifier::class, 'withAccount'),
            array($account)
        );

        $this->getMbox()->shouldReturn(null);
        $this->getMboxSha1Sum()->shouldReturn(null);
        $this->getOpenId()->shouldReturn(null);
        $this->getAccount()->shouldReturn($account);
    }

    function it_is_equal_when_mboxes_are_equal()
    {
        $this->beConstructedThrough('withMbox', array(IRI::fromString('mailto:conformancetest@tincanapi.com')));

        $this->equals(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')))->shouldReturn(true);
    }

    function it_is_equal_when_mbox_sha1_sums_are_equal()
    {
        $this->beConstructedThrough('withMboxSha1Sum', array('db77b9104b531ecbb0b967f6942549d0ba80fda1'));

        $this->equals(InverseFunctionalIdentifier::withMboxSha1Sum('db77b9104b531ecbb0b967f6942549d0ba80fda1'))->shouldReturn(true);
    }

    function it_is_equal_when_open_ids_are_equal()
    {
        $this->beConstructedThrough('withOpenId', array('http://openid.tincanapi.com'));

        $this->equals(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'))->shouldReturn(true);
    }

    function it_is_equal_when_accounts_are_equal()
    {
        $this->beConstructedThrough('withAccount', array(new Account('test', IRL::fromString('https://tincanapi.com'))));

        $this->equals(InverseFunctionalIdentifier::withAccount(new Account('test', IRL::fromString('https://tincanapi.com'))))->shouldReturn(true);
    }

    function its_mbox_value_can_be_retrieved_as_a_string()
    {
        $this->beConstructedWithMbox(IRI::fromString('mailto:conformancetest@tincanapi.com'));

        $this->__toString()->shouldReturn('mailto:conformancetest@tincanapi.com');
    }

    function its_mbox_sha1_sum_value_can_be_retrieved_as_a_string()
    {
        $this->beConstructedWithMboxSha1Sum('db77b9104b531ecbb0b967f6942549d0ba80fda1');

        $this->__toString()->shouldReturn('db77b9104b531ecbb0b967f6942549d0ba80fda1');
    }

    function its_open_id_value_can_be_retrieved_as_a_string()
    {
        $this->beConstructedWithOpenId('http://openid.tincanapi.com');

        $this->__toString()->shouldReturn('http://openid.tincanapi.com');
    }

    function its_account_value_can_be_retrieved_as_a_string()
    {
        $this->beConstructedWithAccount(new Account('test', IRL::fromString('https://tincanapi.com')));

        $this->__toString()->shouldReturn('test (https://tincanapi.com)');
    }
}
