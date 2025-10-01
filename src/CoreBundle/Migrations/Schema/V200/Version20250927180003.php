<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Schema\Schema;
use Exception;

class Version20250927180003 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Save c_item_property.lastedit_user_id as track_e_default for portfolio items and comments';
    }

    /**
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        $itemsRows = $this->connection
            ->executeQuery(
                "SELECT * FROM c_item_property
                    WHERE tool IN ('portfolio', 'portfolio_comment')
                        AND (to_user_id IS NULL OR to_user_id = 0)
                        AND lastedit_type IN ('PortfolioUpdated', 'PortfolioCommentUpdated')"
            )
            ->fetchAllAssociative();

        foreach ($itemsRows as $itemRow) {
            $lastUserId = $itemRow['lastedit_user_id'];
            $courseId = $itemRow['c_id'] ?: null;
            $sessionId = $itemRow['session_id'] ?: null;
            $date = $itemRow['lastedit_date'] ?: $itemRow['insert_date'];

            $eventType = match ($itemRow['lastedit_type']) {
                'PortfolioCommentUpdated' => 'portfolio_comment_updated',
                'PortfolioUpdated' => 'portfolio_updated',
                default => null,
            };

            $valueType = match ($itemRow['lastedit_type']) {
                'PortfolioCommentUpdated' => 'portfolio_comment_id',
                'PortfolioUpdated' => 'portfolio_id',
                default => null,
            };

            if (!$eventType || !$valueType) {
                continue;
            }

            $trackEvent = new TrackEDefault();
            $trackEvent
                ->setDefaultUserId($lastUserId)
                ->setCId($courseId)
                ->setSessionId($sessionId)
                ->setDefaultDate(new DateTime($date, new DateTimeZone('UTC')))
                ->setDefaultEventType($eventType)
                ->setDefaultValueType($valueType)
                ->setDefaultValue((string) $itemRow['ref'])
            ;

            $this->entityManager->persist($trackEvent);
        }

        $this->entityManager->flush();
    }
}
