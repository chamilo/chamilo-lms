<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class MessageExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly SettingsManager $settingsManager
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $qb, string $resourceClass): void
    {
        if (Message::class !== $resourceClass) {
            return;
        }

        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $alias = $qb->getRootAliases()[0];

        /*
         * Existing access rule:
         * - messages sent by the current user, unless deleted
         * - messages received by the current user for inbox/invitation/conversation
         */
        $qb->leftJoin(
            "$alias.receivers",
            'r',
            Join::WITH,
            "r.receiver = :current OR $alias.sender = :current"
        );

        $qb->andWhere(
            "
            ($alias.sender = :current AND $alias.status <> :deleted) OR
            (
                r.receiver = :current AND (
                    $alias.msgType = :inbox OR
                    $alias.msgType = :invitation OR
                    $alias.msgType = :conversation
                )
            )
            "
        );

        $qb->setParameter('current', $user);
        $qb->setParameter('deleted', Message::MESSAGE_STATUS_DELETED);
        $qb->setParameter('inbox', Message::MESSAGE_TYPE_INBOX);
        $qb->setParameter('invitation', Message::MESSAGE_TYPE_INVITATION);
        $qb->setParameter('conversation', Message::MESSAGE_TYPE_CONVERSATION);

        if (!$this->isInteractivityMessageFilterEnabled()) {
            return;
        }

        $this->addSessionTimeframeFilter($qb, $alias);
    }

    private function addSessionTimeframeFilter(QueryBuilder $qb, string $messageAlias): void
    {
        $sessionRelUserClass = SessionRelUser::class;

        /*
         * Use a dedicated receiver alias for the other participant.
         * Do not reuse the access-control alias "r", because for sent messages
         * it can also match the current user's sender/outbox relation.
         */
        $qb->leftJoin(
            "$messageAlias.receivers",
            'timeframePeer',
            Join::WITH,
            'timeframePeer.receiver <> :current'
        );

        /*
         * This setting only applies to conversations between:
         * - current user as session coach/admin
         * - the other participant as learner in the same session
         *
         * If no such relation exists, the current behavior is preserved.
         */
        $hasSessionCoachStudentRelation = "
            EXISTS (
                SELECT coachRel.id
                FROM $sessionRelUserClass coachRel
                JOIN coachRel.session relationSession,
                     $sessionRelUserClass studentRel
                WHERE coachRel.session = studentRel.session
                  AND coachRel.user = :current
                  AND coachRel.relationType IN (:coachRelationTypes)
                  AND studentRel.relationType = :studentRelationType
                  AND (
                        ($messageAlias.sender = :current AND studentRel.user = timeframePeer.receiver)
                        OR
                        ($messageAlias.sender = studentRel.user)
                  )
            )
        ";

        $hasSessionCoachStudentRelationInsideDates = "
            EXISTS (
                SELECT coachDateRel.id
                FROM $sessionRelUserClass coachDateRel
                JOIN coachDateRel.session dateSession,
                     $sessionRelUserClass studentDateRel
                WHERE coachDateRel.session = studentDateRel.session
                  AND coachDateRel.user = :current
                  AND coachDateRel.relationType IN (:coachRelationTypes)
                  AND studentDateRel.relationType = :studentRelationType
                  AND (
                        ($messageAlias.sender = :current AND studentDateRel.user = timeframePeer.receiver)
                        OR
                        ($messageAlias.sender = studentDateRel.user)
                  )
                  AND (dateSession.accessStartDate IS NULL OR $messageAlias.sendDate >= dateSession.accessStartDate)
                  AND (dateSession.accessEndDate IS NULL OR $messageAlias.sendDate <= dateSession.accessEndDate)
                  AND (coachDateRel.accessStartDate IS NULL OR $messageAlias.sendDate >= coachDateRel.accessStartDate)
                  AND (coachDateRel.accessEndDate IS NULL OR $messageAlias.sendDate <= coachDateRel.accessEndDate)
                  AND (studentDateRel.accessStartDate IS NULL OR $messageAlias.sendDate >= studentDateRel.accessStartDate)
                  AND (studentDateRel.accessEndDate IS NULL OR $messageAlias.sendDate <= studentDateRel.accessEndDate)
            )
        ";

        $qb->andWhere(
            \sprintf(
                'NOT (%s) OR (%s)',
                $hasSessionCoachStudentRelation,
                $hasSessionCoachStudentRelationInsideDates
            )
        );

        $qb->setParameter('studentRelationType', Session::STUDENT);
        $qb->setParameter('coachRelationTypes', [
            Session::COURSE_COACH,
            Session::GENERAL_COACH,
            Session::SESSION_ADMIN,
        ]);
    }

    private function isInteractivityMessageFilterEnabled(): bool
    {
        return $this->isSettingEnabled(
            $this->settingsManager->getSetting('message.filter_interactivity_messages', true)
        );
    }

    private function isSettingEnabled(mixed $value): bool
    {
        if (true === $value || 1 === $value) {
            return true;
        }

        $normalized = strtolower(trim((string) $value));

        return 'true' === $normalized || '1' === $normalized;
    }
}
