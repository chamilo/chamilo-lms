<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\DataFixtures;

use Xabbuh\XApi\Model\Account;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\Group;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;

/**
 * xAPI actor fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class ActorFixtures
{
    public static function getTypicalAgent()
    {
        return new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
    }

    public static function getMboxAgent()
    {
        return new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
    }

    public static function getMboxSha1SumAgent()
    {
        return new Agent(InverseFunctionalIdentifier::withMboxSha1Sum('db77b9104b531ecbb0b967f6942549d0ba80fda1'));
    }

    public static function getOpenIdAgent()
    {
        return new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));
    }

    public static function getAccountAgent()
    {
        return new Agent(InverseFunctionalIdentifier::withAccount(AccountFixtures::getTypicalAccount()));
    }

    public static function getForQueryMboxAgent()
    {
        return new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest+query@tincanapi.com')));
    }

    public static function getForQueryMboxSha1SumAgent()
    {
        return new Agent(InverseFunctionalIdentifier::withMboxSha1Sum('6954e807cfbfc5b375d185de32f05de269f93d6f'));
    }

    public static function getForQueryOpenIdAgent()
    {
        return new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com/query'));
    }

    public static function getForQueryAccountAgent()
    {
        return new Agent(InverseFunctionalIdentifier::withAccount(AccountFixtures::getForQueryAccount()));
    }

    public static function getTypicalGroup()
    {
        return new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest-group@tincanapi.com')));
    }

    public static function getMboxGroup()
    {
        return new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest-group@tincanapi.com')));
    }

    public static function getMboxSha1SumGroup()
    {
        return new Group(InverseFunctionalIdentifier::withMboxSha1Sum('4e271041e78101311fb37284ef1a1d35c3f1db35'));
    }

    public static function getOpenIdGroup()
    {
        return new Group(InverseFunctionalIdentifier::withOpenId('http://group.openid.tincanapi.com'));
    }

    public static function getAccountGroup()
    {
        return new Group(InverseFunctionalIdentifier::withAccount(AccountFixtures::getTypicalAccount()));
    }

    public static function getMboxAndNameGroup()
    {
        return new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest-group@tincanapi.com')), 'test group');
    }

    public static function getMboxSha1SumAndNameGroup()
    {
        return new Group(InverseFunctionalIdentifier::withMboxSha1Sum('4e271041e78101311fb37284ef1a1d35c3f1db35'), 'test group');
    }

    public static function getOpenIdAndNameGroup()
    {
        return new Group(InverseFunctionalIdentifier::withOpenId('http://group.openid.tincanapi.com'), 'test group');
    }

    public static function getAccountAndNameGroup()
    {
        return new Group(InverseFunctionalIdentifier::withAccount(AccountFixtures::getTypicalAccount()), 'test group');
    }

    public static function getMboxAndMemberGroup()
    {
        return new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest-group@tincanapi.com')), null, array(self::getTypicalAgent()));
    }

    public static function getMboxSha1SumAndMemberGroup()
    {
        return new Group(InverseFunctionalIdentifier::withMboxSha1Sum('4e271041e78101311fb37284ef1a1d35c3f1db35'), null, array(self::getTypicalAgent()));
    }

    public static function getOpenIdAndMemberGroup()
    {
        return new Group(InverseFunctionalIdentifier::withOpenId('http://group.openid.tincanapi.com'), null, array(self::getTypicalAgent()));
    }

    public static function getAccountAndMemberGroup()
    {
        return new Group(InverseFunctionalIdentifier::withAccount(AccountFixtures::getTypicalAccount()), null, array(self::getTypicalAgent()));
    }

    public static function getAllPropertiesAndTypicalAgentMemberGroup()
    {
        return new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest-group@tincanapi.com')), 'test group', array(self::getTypicalAgent()));
    }

    public static function getAllPropertiesAndMboxAgentMemberGroup()
    {
        return new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest-group@tincanapi.com')), 'test group', array(self::getMboxAgent()));
    }

    public static function getAllPropertiesAndMboxSha1SumAgentMemberGroup()
    {
        return new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest-group@tincanapi.com')), 'test group', array(self::getMboxSha1SumAgent()));
    }

    public static function getAllPropertiesAndOpenIdAgentMemberGroup()
    {
        return new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest-group@tincanapi.com')), 'test group', array(self::getOpenIdAgent()));
    }

    public static function getAllPropertiesAndAccountAgentMemberGroup()
    {
        return new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest-group@tincanapi.com')), 'test group', array(self::getAccountAgent()));
    }

    public static function getAllPropertiesAndTwoTypicalAgentMembersGroup()
    {
        return new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest-group@tincanapi.com')), 'test group', array(self::getTypicalAgent(), self::getTypicalAgent()));
    }
}
