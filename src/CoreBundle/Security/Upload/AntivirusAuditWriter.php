<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Upload;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

final readonly class AntivirusAuditWriter
{
    public function __construct(
        private Connection $connection
    ) {}

    public function record(array $row): void
    {
        // An upload may already be inside an ORM transaction. An independent
        // physical connection keeps the audit row when that transaction rolls back.
        $params = $this->connection->getParams();
        unset($params['wrapperClass']);
        $audit = DriverManager::getConnection($params);

        try {
            $audit->insert('antivirus_scan', $row);
        } finally {
            $audit->close();
        }
    }
}
