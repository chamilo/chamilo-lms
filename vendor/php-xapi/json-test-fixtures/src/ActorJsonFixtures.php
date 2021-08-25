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
 * JSON encoded xAPI actor fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class ActorJsonFixtures extends JsonFixtures
{
    const DIRECTORY = 'Actor';

    public static function getTypicalAgent()
    {
        return self::load('typical_agent');
    }

    public static function getTypicalAgentWithType()
    {
        return self::load('typical_agent_with_type');
    }

    public static function getMboxAgent()
    {
        return self::load('mbox_agent');
    }

    public static function getMboxAgentWithType()
    {
        return self::load('mbox_agent_with_type');
    }

    public static function getMboxSha1SumAgent()
    {
        return self::load('mbox_sha1_sum_agent');
    }

    public static function getMboxSha1SumAgentWithType()
    {
        return self::load('mbox_sha1_sum_agent_with_type');
    }

    public static function getOpenIdAgent()
    {
        return self::load('open_id_agent');
    }

    public static function getOpenIdAgentWithType()
    {
        return self::load('open_id_agent_with_type');
    }

    public static function getAccountAgent()
    {
        return self::load('account_agent');
    }

    public static function getAccountAgentWithType()
    {
        return self::load('account_agent_with_type');
    }

    public static function getForQueryMboxAgent()
    {
        return self::load('for_query_mbox_agent');
    }

    public static function getForQueryMboxSha1SumAgent()
    {
        return self::load('for_query_mbox_sha1_sum_agent');
    }

    public static function getForQueryOpenIdAgent()
    {
        return self::load('for_query_open_id_agent');
    }

    public static function getForQueryAccountAgent()
    {
        return self::load('for_query_account_agent');
    }

    public static function getTypicalGroup()
    {
        return self::load('typical_group');
    }

    public static function getMboxGroup()
    {
        return self::load('mbox_group');
    }

    public static function getMboxSha1SumGroup()
    {
        return self::load('mbox_sha1_sum_group');
    }

    public static function getOpenIdGroup()
    {
        return self::load('open_id_group');
    }

    public static function getAccountGroup()
    {
        return self::load('account_group');
    }

    public static function getMboxAndNameGroup()
    {
        return self::load('mbox_and_name_group');
    }

    public static function getMboxSha1SumAndNameGroup()
    {
        return self::load('mbox_sha1_sum_and_name_group');
    }

    public static function getOpenIdAndNameGroup()
    {
        return self::load('open_id_and_name_group');
    }

    public static function getAccountAndNameGroup()
    {
        return self::load('account_and_name_group');
    }

    public static function getMboxAndMemberGroup()
    {
        return self::load('mbox_and_member_group');
    }

    public static function getMboxSha1SumAndMemberGroup()
    {
        return self::load('mbox_sha1_sum_and_member_group');
    }

    public static function getOpenIdAndMemberGroup()
    {
        return self::load('open_id_and_member_group');
    }

    public static function getAccountAndMemberGroup()
    {
        return self::load('account_and_member_group');
    }

    public static function getAllPropertiesAndTypicalAgentMemberGroup()
    {
        return self::load('all_properties_and_typical_agent_member_group');
    }

    public static function getAllPropertiesAndMboxAgentMemberGroup()
    {
        return self::load('all_properties_and_mbox_agent_member_group');
    }

    public static function getAllPropertiesAndMboxSha1SumAgentMemberGroup()
    {
        return self::load('all_properties_and_mbox_sha1_sum_agent_member_group');
    }

    public static function getAllPropertiesAndOpenIdAgentMemberGroup()
    {
        return self::load('all_properties_and_open_id_agent_member_group');
    }

    public static function getAllPropertiesAndAccountAgentMemberGroup()
    {
        return self::load('all_properties_and_account_agent_member_group');
    }

    public static function getAllPropertiesAndTwoTypicalAgentMembersGroup()
    {
        return self::load('all_properties_and_two_typical_agent_members_group');
    }
}
