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

use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementReference;
use Xabbuh\XApi\Model\SubStatement;
use Xabbuh\XApi\Model\Verb;

/**
 * xAPI statement fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class StatementFixtures
{
    const DEFAULT_STATEMENT_ID = '12345678-1234-5678-8234-567812345678';

    public static function getMinimalStatement($id = self::DEFAULT_STATEMENT_ID)
    {
        if (null === $id) {
            $id = UuidFixtures::getUniqueUuid();
        }

        return new Statement(StatementId::fromString($id), ActorFixtures::getTypicalAgent(), VerbFixtures::getTypicalVerb(), ActivityFixtures::getTypicalActivity());
    }

    public static function getTypicalStatement($id = self::DEFAULT_STATEMENT_ID)
    {
        if (null === $id) {
            $id = UuidFixtures::getUniqueUuid();
        }

        return new Statement(StatementId::fromString($id), ActorFixtures::getTypicalAgent(), VerbFixtures::getTypicalVerb(), ActivityFixtures::getTypicalActivity(), null, null, new \DateTime('2014-07-23T12:34:02-05:00'));
    }

    public static function getVoidingStatement($id = null, $voidedStatementId = 'e05aa883-acaf-40ad-bf54-02c8ce485fb0')
    {
        if (null === $id) {
            $id = UuidFixtures::getUniqueUuid();
        }

        return new Statement(StatementId::fromString($id), ActorFixtures::getTypicalAgent(), VerbFixtures::getVoidingVerb(), new StatementReference(StatementId::fromString($voidedStatementId)));
    }

    public static function getAttachmentStatement()
    {
        return new Statement(null, ActorFixtures::getTypicalAgent(), VerbFixtures::getTypicalVerb(), ActivityFixtures::getTypicalActivity(), null, null, null, null, null, array(AttachmentFixtures::getTextAttachment()));
    }

    /**
     * Loads a statement with a group as an actor.
     *
     * @param string $id The id of the new Statement
     *
     * @return Statement
     */
    public static function getStatementWithGroupActor($id = self::DEFAULT_STATEMENT_ID)
    {
        if (null === $id) {
            $id = UuidFixtures::getUniqueUuid();
        }

        $group = ActorFixtures::getTypicalGroup();
        $verb = VerbFixtures::getTypicalVerb();
        $activity = ActivityFixtures::getTypicalActivity();

        return new Statement(StatementId::fromString($id), $group, $verb, $activity);
    }

    /**
     * Loads a statement with a group that has no members as an actor.
     *
     * @param string $id The id of the new Statement
     *
     * @return Statement
     */
    public static function getStatementWithGroupActorWithoutMembers($id = self::DEFAULT_STATEMENT_ID)
    {
        if (null === $id) {
            $id = UuidFixtures::getUniqueUuid();
        }

        $group = ActorFixtures::getTypicalGroup();
        $verb = VerbFixtures::getTypicalVerb();
        $activity = ActivityFixtures::getTypicalActivity();

        return new Statement(StatementId::fromString($id), $group, $verb, $activity);
    }

    /**
     * Loads a statement including a reference to another statement.
     *
     * @param string $id The id of the new Statement
     *
     * @return Statement
     */
    public static function getStatementWithStatementRef($id = self::DEFAULT_STATEMENT_ID)
    {
        $minimalStatement = static::getMinimalStatement($id);

        return new Statement(
            $minimalStatement->getId(),
            $minimalStatement->getActor(),
            $minimalStatement->getVerb(),
            new StatementReference(StatementId::fromString('8f87ccde-bb56-4c2e-ab83-44982ef22df0'))
        );
    }

    /**
     * Loads a statement including a result.
     *
     * @param string $id The id of the new Statement
     *
     * @return Statement
     */
    public static function getStatementWithResult($id = self::DEFAULT_STATEMENT_ID)
    {
        if (null === $id) {
            $id = UuidFixtures::getUniqueUuid();
        }

        return new Statement(StatementId::fromString($id), ActorFixtures::getTypicalAgent(), VerbFixtures::getTypicalVerb(), ActivityFixtures::getTypicalActivity(), ResultFixtures::getScoreAndDurationResult());
    }

    /**
     * Loads a statement including a sub statement.
     *
     * @param string $id The id of the new Statement
     *
     * @return Statement
     */
    public static function getStatementWithSubStatement($id = self::DEFAULT_STATEMENT_ID)
    {
        if (null === $id) {
            $id = UuidFixtures::getUniqueUuid();
        }

        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:test@example.com')));
        $verb = new Verb(IRI::fromString('http://example.com/visited'), LanguageMap::create(array('en-US' => 'will visit')));
        $definition = new Definition(
            LanguageMap::create(array('en-US' => 'Some Awesome Website')),
            LanguageMap::create(array('en-US' => 'The visited website')),
            IRI::fromString('http://example.com/definition-type')
        );
        $activity = new Activity(IRI::fromString('http://example.com/website'), $definition);
        $subStatement = new SubStatement($actor, $verb, $activity);

        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:test@example.com')));
        $verb = new Verb(IRI::fromString('http://example.com/planned'), LanguageMap::create(array('en-US' => 'planned')));

        return new Statement(StatementId::fromString($id), $actor, $verb, $subStatement);
    }

    /**
     * Loads a statement including an agent authority.
     *
     * @param string $id The id of the new Statement
     *
     * @return Statement
     */
    public static function getStatementWithAgentAuthority($id = self::DEFAULT_STATEMENT_ID)
    {
        if (null === $id) {
            $id = UuidFixtures::getUniqueUuid();
        }

        return new Statement(StatementId::fromString($id), ActorFixtures::getTypicalAgent(), VerbFixtures::getTypicalVerb(), ActivityFixtures::getTypicalActivity(), null, ActorFixtures::getAccountAgent());
    }

    /**
     * Loads a statement including a group authority.
     *
     * @param string $id The id of the new Statement
     *
     * @return Statement
     */
    public static function getStatementWithGroupAuthority($id = self::DEFAULT_STATEMENT_ID)
    {
        if (null === $id) {
            $id = UuidFixtures::getUniqueUuid();
        }

        return new Statement(StatementId::fromString($id), ActorFixtures::getTypicalAgent(), VerbFixtures::getTypicalVerb(), ActivityFixtures::getTypicalActivity(), null, ActorFixtures::getTypicalGroup());
    }

    public static function getAllPropertiesStatement($id = self::DEFAULT_STATEMENT_ID)
    {
        if (null === $id) {
            $id = UuidFixtures::getUniqueUuid();
        }

        return new Statement(
            StatementId::fromString($id),
            ActorFixtures::getTypicalAgent(),
            VerbFixtures::getTypicalVerb(),
            ActivityFixtures::getTypicalActivity(),
            ResultFixtures::getAllPropertiesResult(),
            ActorFixtures::getAccountAgent(),
            new \DateTime('2013-05-18T05:32:34+00:00'),
            new \DateTime('2014-07-23T12:34:02-05:00'),
            ContextFixtures::getAllPropertiesContext(),
            array(AttachmentFixtures::getTextAttachment())
        );
    }

    /**
     * @return Statement[]
     */
    public static function getStatementCollection()
    {
        return array(
            self::getMinimalStatement('12345678-1234-5678-8234-567812345678'),
            self::getMinimalStatement('12345678-1234-5678-8234-567812345679'),
        );
    }
}
