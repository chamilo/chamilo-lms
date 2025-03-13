<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

trait XApiActivityTrait
{
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function saveSharedStatement(Statement $statement): SharedStatement
    {
        $statementSerialized = $this->serializeStatement($statement);

        $sharedStmt = new SharedStatement(
            json_decode($statementSerialized, true)
        );

        $em = Database::getManager();
        $em->persist($sharedStmt);
        $em->flush();

        return $sharedStmt;
    }

    private function serializeStatement(Statement $statement)
    {
        $serializer = Serializer::createSerializer();
        $statementSerializer = new StatementSerializer($serializer);

        return $statementSerializer->serializeStatement($statement);
    }
}