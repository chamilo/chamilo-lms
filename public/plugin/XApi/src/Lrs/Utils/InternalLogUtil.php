<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs\Util;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\XApiInternalLog;
use Chamilo\CoreBundle\Framework\Container;
use Database;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Actor;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\Statement;
use XApiPlugin;

/**
 * Utility used to mirror xAPI statements into Chamilo internal logs.
 */
class InternalLogUtil
{
    public static function saveStatementForInternalLog(Statement $statement): void
    {
        $user = self::getUserFromActor($statement->getActor());
        if (null === $user) {
            return;
        }

        $statementObject = $statement->getObject();
        if (!$statementObject instanceof Activity) {
            return;
        }

        $languageIso = api_get_language_isocode();
        if (empty($languageIso)) {
            $languageIso = 'en';
        }

        $statementVerbString = XApiPlugin::extractVerbInLanguage(
            $statement->getVerb()->getDisplay(),
            $languageIso
        );

        $internalLog = new XApiInternalLog();
        $internalLog
            ->setUser($user)
            ->setVerb($statementVerbString)
            ->setObjectId($statementObject->getId()->getValue())
        ;

        $statementId = $statement->getId();
        if (null !== $statementId) {
            $internalLog->setStatementId($statementId->getValue());
        }

        $definition = $statementObject->getDefinition();
        if (null !== $definition) {
            $nameInLanguages = $definition->getName();
            if (null !== $nameInLanguages) {
                $internalLog->setActivityName(
                    XApiPlugin::extractVerbInLanguage($nameInLanguages, $languageIso)
                );
            }

            $descriptionInLanguages = $definition->getDescription();
            if (null !== $descriptionInLanguages) {
                $internalLog->setActivityDescription(
                    XApiPlugin::extractVerbInLanguage($descriptionInLanguages, $languageIso)
                );
            }
        }

        $statementResult = $statement->getResult();
        if (null !== $statementResult) {
            $score = $statementResult->getScore();
            if (null !== $score) {
                $internalLog
                    ->setScoreScaled($score->getScaled())
                    ->setScoreRaw($score->getRaw())
                    ->setScoreMin($score->getMin())
                    ->setScoreMax($score->getMax())
                ;
            }
        }

        $created = $statement->getCreated();
        if (null !== $created) {
            $internalLog->setCreatedAt($created);
        }

        $em = Database::getManager();
        $em->persist($internalLog);
        $em->flush();
    }

    private static function getUserFromActor(Actor $actor): ?User
    {
        if (!$actor instanceof Agent) {
            return null;
        }

        $actorIdentifier = $actor->getInverseFunctionalIdentifier();
        if (null === $actorIdentifier) {
            return null;
        }

        $userRepo = Container::getUserRepository();

        if (null !== $mbox = $actorIdentifier->getMbox()) {
            $parts = explode(':', $mbox->getValue(), 2);
            if (!empty($parts[1])) {
                /** @var User|null $user */
                $user = $userRepo->findOneBy(['email' => $parts[1]]);

                return $user;
            }
        }

        if (null !== $account = $actorIdentifier->getAccount()) {
            $chamiloIrl = IRL::fromString(api_get_path(WEB_PATH));

            if ($account->getHomePage()->equals($chamiloIrl)) {
                /** @var User|null $user */
                $user = $userRepo->findOneBy(['username' => $account->getName()]);

                return $user;
            }
        }

        return null;
    }
}
