<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Entity\XApiSharedStatement;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Uid\Uuid;

trait XApiActivityTrait
{
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function saveSharedStatement(array $statement): void
    {
        $normalizedStatement = $this->normalizeSharedStatement($statement);

        $sharedStmt = new XApiSharedStatement(
            $normalizedStatement,
            null,
            false
        );

        $em = Database::getManager();
        $em->persist($sharedStmt);
        $em->flush();
    }

    private function normalizeSharedStatement(array $statement): array
    {
        if (empty($statement['actor']) || !is_array($statement['actor'])) {
            throw new InvalidArgumentException('xAPI statement actor is required.');
        }

        if (empty($statement['verb']) || !is_array($statement['verb'])) {
            throw new InvalidArgumentException('xAPI statement verb is required.');
        }

        if (empty($statement['object']) || !is_array($statement['object'])) {
            throw new InvalidArgumentException('xAPI statement object is required.');
        }

        if (empty($statement['id']) || !is_string($statement['id'])) {
            $statement['id'] = Uuid::v4()->toRfc4122();
        }

        if (empty($statement['timestamp']) || !is_string($statement['timestamp'])) {
            $statement['timestamp'] = api_get_utc_datetime(null, false, true)->format(DATE_ATOM);
        }

        if (isset($statement['context']) && !is_array($statement['context'])) {
            unset($statement['context']);
        }

        if (isset($statement['result']) && !is_array($statement['result'])) {
            unset($statement['result']);
        }

        if (isset($statement['attachments']) && !is_array($statement['attachments'])) {
            unset($statement['attachments']);
        }

        return $statement;
    }
}
