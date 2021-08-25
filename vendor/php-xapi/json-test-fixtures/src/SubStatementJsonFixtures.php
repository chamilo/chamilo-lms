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
 * JSON encoded xAPI sub statement fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class SubStatementJsonFixtures extends JsonFixtures
{
    const DIRECTORY = 'SubStatement';

    public static function getTypicalSubStatement()
    {
        return self::load('typical');
    }

    public static function getSubStatementWithMboxAgent()
    {
        return self::load('mbox_agent_actor');
    }

    public static function getSubStatementWithMboxSha1SumAgent()
    {
        return self::load('mbox_sha1_sum_agent_actor');
    }

    public static function getSubStatementWithOpenIdAgent()
    {
        return self::load('open_id_agent_actor');
    }

    public static function getSubStatementWithAccountAgent()
    {
        return self::load('account_agent_actor');
    }

    public static function getSubStatementWithMboxAgentWithType()
    {
        return self::load('mbox_agent_with_type_actor');
    }

    public static function getSubStatementWithMboxSha1SumAgentWithType()
    {
        return self::load('mbox_sha1_sum_agent_with_type_actor');
    }

    public static function getSubStatementWithOpenIdAgentWithType()
    {
        return self::load('open_id_agent_with_type_actor');
    }

    public static function getSubStatementWithAccountAgentWithType()
    {
        return self::load('account_agent_with_type_actor');
    }

    public static function getSubStatementWithMboxGroup()
    {
        return self::load('mbox_group_actor');
    }

    public static function getSubStatementWithMboxSha1SumGroup()
    {
        return self::load('mbox_sha1_sum_group_actor');
    }

    public static function getSubStatementWithOpenIdGroup()
    {
        return self::load('open_id_group_actor');
    }

    public static function getSubStatementWithAccountGroup()
    {
        return self::load('account_group_actor');
    }

    public static function getSubStatementWithIdOnlyVerb()
    {
        return self::load('id_verb');
    }

    public static function getSubStatementWithMboxAgentObject()
    {
        return self::load('mbox_agent_object');
    }

    public static function getSubStatementWithMboxSha1SumAgentObject()
    {
        return self::load('mbox_sha1_sum_agent_object');
    }

    public static function getSubStatementWithOpenIdAgentObject()
    {
        return self::load('open_id_agent_object');
    }

    public static function getSubStatementWithAccountAgentObject()
    {
        return self::load('account_agent_object');
    }

    public static function getSubStatementWithMboxAgentObjectWithType()
    {
        return self::load('mbox_agent_with_type_object');
    }

    public static function getSubStatementWithMboxSha1SumAgentObjectWithType()
    {
        return self::load('mbox_sha1_sum_agent_with_type_object');
    }

    public static function getSubStatementWithOpenIdAgentObjectWithType()
    {
        return self::load('open_id_agent_with_type_object');
    }

    public static function getSubStatementWithAccountAgentObjectWithType()
    {
        return self::load('account_agent_with_type_object');
    }

    public static function getSubStatementWithMboxGroupObject()
    {
        return self::load('mbox_group_object');
    }

    public static function getSubStatementWithMboxSha1SumGroupObject()
    {
        return self::load('mbox_sha1_sum_group_object');
    }

    public static function getSubStatementWithOpenIdGroupObject()
    {
        return self::load('open_id_group_object');
    }

    public static function getSubStatementWithAccountGroupObject()
    {
        return self::load('account_group_object');
    }

    public static function getSubStatementWithAllPropertiesAndTypicalAgentMemberGroupObject()
    {
        return self::load('all_properties_typical_agent_member_group_object');
    }

    public static function getSubStatementWithAllPropertiesActivityObject()
    {
        return self::load('all_properties_activity_object');
    }

    public static function getSubStatementWithTypicalStatementReferenceObject()
    {
        return self::load('typical_statement_reference_object');
    }

    public static function getAllPropertiesSubStatement()
    {
        return self::load('all_properties');
    }
}
