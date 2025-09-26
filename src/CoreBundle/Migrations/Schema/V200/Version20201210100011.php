<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;

class Version20201210100011 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add access start and end dates to session_rel_user table and migrate dates from first course access';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE session_rel_user ADD access_start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)'");
        $this->addSql("ALTER TABLE session_rel_user ADD access_end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)'");

        $sessions = $this->getSessionWithNoDuration();

        foreach ($sessions as $session) {
            $sRUs = $this->getSessionRelUsers($session['id']);

            foreach ($sRUs as $sru) {
                $isCoach = $this->isCoach($session['id'], $sru['user_id']);

                $startDate = $isCoach && $session['coach_access_start_date']
                    ? $session['coach_access_start_date']
                    : $session['access_start_date'];

                $endDate = $isCoach && $session['coach_access_end_date']
                    ? $session['coach_access_end_date']
                    : $session['access_end_date'];

                $this->addSql(
                    'UPDATE session_rel_user SET access_start_date = :startDate, access_end_date = :endDate WHERE id = :id',
                    [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'id' => $sru['id'],
                    ]
                );
            }
        }

        $sessions = $this->getSessionWithDuration();

        foreach ($sessions as $session) {
            $sRUs = $this->getSessionRelUsers($session['id']);

            foreach ($sRUs as $sru) {
                $duration = (int) $session['duration'] + (int) $sru['duration'];

                $firstAccessToSession = $this->getFirstAccessToSession($session['id'], $sru['user_id']);

                $calculatedLastAccessToSession = $firstAccessToSession + $duration * 24 * 60 * 60;

                $startDate = $firstAccessToSession ?: null;
                $endDate = $firstAccessToSession ? $calculatedLastAccessToSession : null;

                $this->addSql(
                    'UPDATE session_rel_user
                        SET access_start_date = FROM_UNIXTIME(:startDate), access_end_date = FROM_UNIXTIME(:endDate)
                        WHERE id = :id',
                    [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'id' => $sru['id'],
                    ]
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    private function getSessionWithNoDuration(): array
    {
        return $this->connection
            ->executeQuery(
                'SELECT
                    id,
                    display_start_date, display_end_date,
                    access_start_date, access_end_date,
                    coach_access_start_date, coach_access_end_date
                FROM session
                WHERE (duration IS NULL OR duration = 0)'
            )
            ->fetchAllAssociative()
        ;
    }

    /**
     * @throws Exception
     */
    private function getSessionRelUsers(int $sessionId): array
    {
        return $this->connection
            ->executeQuery(
                'SELECT id, user_id, duration FROM session_rel_user WHERE session_id = :sessionId',
                ['sessionId' => $sessionId]
            )
            ->fetchAllAssociative()
        ;
    }

    /**
     * @throws Exception
     */
    private function isCoach(int $sessionId, int $userId): bool
    {
        $sCRUs = $this->connection
            ->executeQuery(
                'SELECT COUNT(1) AS count_as_coach
                    FROM session_rel_course_rel_user
                    WHERE session_id = :sessionId
                        AND user_id = :userId
                        AND (status = :status_coach OR status = :status_general_coach)',
                [
                    'sessionId' => $sessionId,
                    'userId' => $userId,
                    'status_coach' => Session::COURSE_COACH,
                    'status_general_coach' => Session::GENERAL_COACH,
                ]
            )
            ->fetchAllAssociative()
        ;

        return $sCRUs[0]['count_as_coach'] > 0;
    }

    /**
     * @throws Exception
     */
    private function getSessionWithDuration(): array
    {
        return $this->connection
            ->executeQuery(
                'SELECT id, duration
                FROM session
                WHERE (duration IS NOT NULL AND duration > 0)'
            )
            ->fetchAllAssociative()
        ;
    }

    /**
     * @throws Exception
     */
    private function getFirstAccessToSession(int $sessionId, int $userId): int
    {
        $access = $this->connection
            ->executeQuery(
                'SELECT UNIX_TIMESTAMP(login_course_date) as tms
                    FROM track_e_course_access
                    WHERE session_id = :sessionId AND user_id = :userId
                    ORDER BY login_course_date ASC
                    LIMIT 1',
                [
                    'sessionId' => $sessionId,
                    'userId' => $userId,
                ]
            )
            ->fetchAllAssociative()
        ;

        if ($access) {
            return (int) $access[0]['tms'];
        }

        return 0;
    }
}
