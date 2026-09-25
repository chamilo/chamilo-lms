<?php

// Sync this changes with the config/packages/doctrine_migrations.yaml file

return [
    'table_storage' => [
        'table_name' => 'version',
        'version_column_name' => 'version',
        // Keep the primary key below MySQL/InnoDB's 3072-byte index limit with utf8mb4.
        'version_column_length' => 191,
        'executed_at_column_name' => 'executed_at',
        'execution_time_column_name' => 'execution_time',
    ],
    'migrations_paths' => [
        'Chamilo\CoreBundle\Migrations\Schema\V200' => '../../../src/CoreBundle/Migrations/Schema/V200',
        'Chamilo\CoreBundle\Migrations\Schema\V210' => '../../../src/CoreBundle/Migrations/Schema/V210',
        'Chamilo\CoreBundle\Migrations\Schema\V300' => '../../../src/CoreBundle/Migrations/Schema/V300',
        'Chamilo\CoreBundle\Migrations\Schema\V310' => '../../../src/CoreBundle/Migrations/Schema/V310',
    ],
    'all_or_nothing' => false,
    'check_database_platform' => true,
];
