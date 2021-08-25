<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Fixtures\Json;

/**
 * JSON encoded xAPI context fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class ContextJsonFixtures extends JsonFixtures
{
    const DIRECTORY = 'Context';

    public static function getEmptyContext()
    {
        return self::load('empty');
    }

    public static function getTypicalContext()
    {
        return self::load('typical');
    }

    public static function getTypicalAgentInstructorContext()
    {
        return self::load('typical_agent_instructor');
    }

    public static function getMboxAgentInstructorContext()
    {
        return self::load('mbox_agent_instructor');
    }

    public static function getMboxAgentInstructorContextWithType()
    {
        return self::load('mbox_agent_with_type_instructor');
    }

    public static function getMboxSha1SumAgentInstructorContext()
    {
        return self::load('mbox_sha1_sum_agent_instructor');
    }

    public static function getMboxSha1SumAgentInstructorContextWithType()
    {
        return self::load('mbox_sha1_sum_agent_with_type_instructor');
    }

    public static function getOpenIdAgentInstructorContext()
    {
        return self::load('open_id_agent_instructor');
    }

    public static function getOpenIdAgentInstructorContextWithType()
    {
        return self::load('open_id_agent_with_type_instructor');
    }

    public static function getAccountAgentInstructorContext()
    {
        return self::load('account_agent_instructor');
    }

    public static function getAccountAgentInstructorContextWithType()
    {
        return self::load('account_agent_with_type_instructor');
    }

    public static function getTypicalGroupTeamContext()
    {
        return self::load('typical_group_team');
    }

    public static function getStatementOnlyContext()
    {
        return self::load('statement_only');
    }

    public static function getExtensionsOnlyContext()
    {
        return self::load('extensions_only');
    }

    public static function getEmptyExtensionsOnlyContext()
    {
        return self::load('empty_extensions_only');
    }

    public static function getEmptyContextActivitiesContext()
    {
        return self::load('empty_context_activities');
    }

    public static function getEmptyContextActivitiesAllPropertiesEmptyContext()
    {
        return self::load('empty_context_activities_all_properties_empty');
    }

    public static function getContextActivitiesAllPropertiesOnlyContext()
    {
        return self::load('context_activities_all_properties_only');
    }

    public static function getAllPropertiesContext()
    {
        return self::load('all_properties');
    }
}
