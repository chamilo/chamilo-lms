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
 * JSON encoded statement fixtures.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class StatementJsonFixtures extends JsonFixtures
{
    const DIRECTORY = 'Statement';

    /**
     * Loads a minimal valid statement.
     *
     * @return string
     */
    public static function getMinimalStatement()
    {
        return static::load('minimal');
    }

    public static function getTypicalStatement()
    {
        return static::load('typical');
    }

    public static function getVoidingStatement()
    {
        return static::load('voiding');
    }

    public static function getAttachmentStatement()
    {
        return static::load('attachment');
    }

    /**
     * Loads a statement with a group as an actor.
     *
     * @return string
     */
    public static function getStatementWithGroupActor()
    {
        return static::load('with_group_actor');
    }

    /**
     * Loads a statement with a group that has no members as an actor.
     *
     * @return string
     */
    public static function getStatementWithGroupActorWithoutMembers()
    {
        return static::load('with_group_actor_without_members');
    }

    /**
     * Loads a statement including a reference to another statement.
     *
     * @return string
     */
    public static function getStatementWithStatementRef()
    {
        return static::load('with_statement_ref');
    }

    /**
     * Loads a statement including a result.
     *
     * @return string
     */
    public static function getStatementWithResult()
    {
        return static::load('with_result');
    }

    /**
     * Loads a statement including a sub statement.
     *
     * @return string
     */
    public static function getStatementWithSubStatement()
    {
        return static::load('with_sub_statement');
    }

    /**
     * Loads a statement including an agent authority.
     *
     * @return string
     */
    public static function getStatementWithAgentAuthority()
    {
        return static::load('with_agent_authority');
    }

    /**
     * Loads a statement including a group authority.
     *
     * @return string
     */
    public static function getStatementWithGroupAuthority()
    {
        return static::load('with_group_authority');
    }

    public static function getAllPropertiesStatement()
    {
        return static::load('all_properties');
    }

    /**
     * Loads a collection of statements.
     *
     * @return string
     */
    public static function getStatementCollection()
    {
        return static::load('collection');
    }
}
