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

use Xabbuh\XApi\Model\Context;

/**
 * xAPI context fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class ContextFixtures
{
    public static function getEmptyContext()
    {
        return new Context();
    }

    public static function getTypicalContext()
    {
        return new Context();
    }

    public static function getTypicalAgentInstructorContext()
    {
        $context = new Context();

        return $context->withInstructor(ActorFixtures::getTypicalAgent());
    }

    public static function getMboxAgentInstructorContext()
    {
        $context = new Context();

        return $context->withInstructor(ActorFixtures::getMboxAgent());
    }

    public static function getMboxSha1SumAgentInstructorContext()
    {
        $context = new Context();

        return $context->withInstructor(ActorFixtures::getMboxSha1SumAgent());
    }

    public static function getOpenIdAgentInstructorContext()
    {
        $context = new Context();

        return $context->withInstructor(ActorFixtures::getOpenIdAgent());
    }

    public static function getAccountAgentInstructorContext()
    {
        $context = new Context();

        return $context->withInstructor(ActorFixtures::getAccountAgent());
    }

    public static function getTypicalGroupTeamContext()
    {
        $context = new Context();

        return $context->withTeam(ActorFixtures::getTypicalGroup());
    }

    public static function getStatementOnlyContext()
    {
        $context = new Context();

        return $context->withStatement(StatementReferenceFixtures::getTypicalStatementReference());
    }

    public static function getExtensionsOnlyContext()
    {
        $context = new Context();

        return $context->withExtensions(ExtensionsFixtures::getTypicalExtensions());
    }

    public static function getEmptyExtensionsOnlyContext()
    {
        $context = new Context();

        return $context->withExtensions(ExtensionsFixtures::getEmptyExtensions());
    }

    public static function getEmptyContextActivitiesContext()
    {
        $context = new Context();

        return $context->withContextActivities(ContextActivitiesFixtures::getEmptyContextActivities());
    }

    public static function getEmptyContextActivitiesAllPropertiesEmptyContext()
    {
        $context = new Context();

        return $context->withContextActivities(ContextActivitiesFixtures::getAllPropertiesEmptyActivities());
    }

    public static function getContextActivitiesAllPropertiesOnlyContext()
    {
        $context = new Context();

        return $context->withContextActivities(ContextActivitiesFixtures::getAllPropertiesActivities());
    }

    public static function getAllPropertiesContext()
    {
        $context = new Context();
        $context = $context->withRegistration('16fd2706-8baf-433b-82eb-8c7fada847da')
            ->withInstructor(ActorFixtures::getTypicalAgent())
            ->withTeam(ActorFixtures::getTypicalGroup())
            ->withContextActivities(ContextActivitiesFixtures::getAllPropertiesActivities())
            ->withRevision('test')
            ->withPlatform('test')
            ->withLanguage('en-US')
            ->withStatement(StatementReferenceFixtures::getTypicalStatementReference())
            ->withExtensions(ExtensionsFixtures::getTypicalExtensions())
        ;

        return $context;
    }
}
