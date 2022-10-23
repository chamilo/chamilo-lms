<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs\Util;

use Chamilo\PluginBundle\Entity\XApi\InternalLog;
use Database;
use UserManager;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Actor;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\Statement;
use XApiPlugin;

class InternalLogUtil
{
    public static function saveStatementForInternalLog(Statement $statement)
    {
        if (null === $user = self::getUserFromActor($statement->getActor())) {
            return;
        }

        $statementObject = $statement->getObject();

        if (!$statementObject instanceof Activity) {
            return;
        }

        $languageIso = api_get_language_isocode();
        $statementVerbString = XApiPlugin::extractVerbInLanguage($statement->getVerb()->getDisplay(), $languageIso);

        $internalLog = new InternalLog();
        $internalLog
            ->setUser($user)
            ->setVerb($statementVerbString)
            ->setObjectId($statementObject->getId()->getValue());

        if (null !== $statementId = $statement->getId()) {
            $internalLog->setStatementId($statementId->getValue());
        }

        if (null !== $definition = $statementObject->getDefinition()) {
            if (null !== $nameInLanguages = $definition->getName()) {
                $internalLog->setActivityName(
                    XApiPlugin::extractVerbInLanguage($nameInLanguages, $languageIso)
                );
            }

            if (null !== $descriptionInLanguage = $definition->getDescription()) {
                $internalLog->setActivityDescription(
                    XApiPlugin::extractVerbInLanguage($descriptionInLanguage, $languageIso)
                );
            }
        }

        if (null !== $statementResult = $statement->getResult()) {
            if (null !== $score = $statementResult->getScore()) {
                $internalLog
                    ->setScoreScaled(
                        $score->getScaled()
                    )
                    ->setScoreRaw(
                        $score->getRaw()
                    )
                    ->setScoreMin(
                        $score->getMin()
                    )
                    ->setScoreMax(
                        $score->getMax()
                    );
            }
        }

        if (null !== $created = $statement->getCreated()) {
            $internalLog->setCreatedAt($created);
        }

        $em = Database::getManager();
        $em->persist($internalLog);
        $em->flush();
    }

    private static function getUserFromActor(Actor $actor): ?object
    {
        if (!$actor instanceof Agent) {
            return null;
        }

        $actorIri = $actor->getInverseFunctionalIdentifier();

        if (null === $actorIri) {
            return null;
        }

        $userRepo = UserManager::getRepository();

        $user = null;

        if (null !== $mbox = $actorIri->getMbox()) {
            if ((null !== $parts = explode(':', $mbox->getValue(), 2)) && !empty($parts[1])) {
                $user = $userRepo->findOneBy(['email' => $parts[1]]);
            }
        } elseif (null !== $account = $actorIri->getAccount()) {
            $chamiloIrl = IRL::fromString(api_get_path(WEB_PATH));

            if ($account->getHomePage()->equals($chamiloIrl)) {
                $user = $userRepo->findOneBy(['username' => $account->getName()]);
            }
        }

        return $user;
    }
}
