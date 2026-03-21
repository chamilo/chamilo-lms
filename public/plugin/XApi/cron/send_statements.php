<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\XApiSharedStatement;
use Symfony\Component\Uid\Uuid;

require_once __DIR__.'/../../../main/inc/global.inc.php';

if (PHP_SAPI !== 'cli') {
    exit;
}

echo 'XAPI: Cron to send statements.'.PHP_EOL;

$em = Database::getManager();

/** @var XApiSharedStatement[] $notSentSharedStatements */
$notSentSharedStatements = $em
    ->getRepository(XApiSharedStatement::class)
    ->findBy(
        ['sent' => false],
        ['id' => 'ASC'],
        100
    )
;

$countNotSent = count($notSentSharedStatements);

if (0 === $countNotSent) {
    echo 'No statements to process.'.PHP_EOL;

    return;
}

echo '['.time().'] Trying to send '.$countNotSent.' statements to LRS'.PHP_EOL;

$client = XApiPlugin::create()->getXapiStatementCronClient();

foreach ($notSentSharedStatements as $notSentSharedStatement) {
    $statement = $notSentSharedStatement->getStatement();

    if (!is_array($statement) || empty($statement)) {
        echo '['.time()."] Shared statement ({$notSentSharedStatement->getId()}) has an empty payload. Skipping.".PHP_EOL;

        continue;
    }

    $statementId = null;

    if (isset($statement['id']) && is_string($statement['id']) && '' !== trim($statement['id'])) {
        $statementId = trim($statement['id']);
    } elseif (null !== $notSentSharedStatement->getUuid()) {
        $statementId = $notSentSharedStatement->getUuid()->toRfc4122();
        $statement['id'] = $statementId;

        $notSentSharedStatement->setStatement($statement);
        $em->persist($notSentSharedStatement);
    } else {
        $statementId = Uuid::v4()->toRfc4122();
        $statement['id'] = $statementId;

        $notSentSharedStatement->setStatement($statement);
        $em->persist($notSentSharedStatement);
    }

    try {
        echo '['.time()."] Sending shared statement ({$notSentSharedStatement->getId()})".PHP_EOL;

        $client->storeStatement($statement);

        $notSentSharedStatement
            ->setUuid(Uuid::fromString($statementId))
            ->setSent(true)
        ;

        $em->persist($notSentSharedStatement);
        $em->flush();

        echo '['.time()."] Statement sent successfully. Statement ID: {$statementId}".PHP_EOL;
    } catch (\Throwable $exception) {
        echo '['.time()."] Failed to send shared statement ({$notSentSharedStatement->getId()}): {$exception->getMessage()}".PHP_EOL;

        $em->clear();

        $reloadedStatement = $em->find(XApiSharedStatement::class, $notSentSharedStatement->getId());

        if (!$reloadedStatement) {
            echo '['.time()."] Shared statement entity could not be reloaded after failure.".PHP_EOL;
        }

        continue;
    }
}
