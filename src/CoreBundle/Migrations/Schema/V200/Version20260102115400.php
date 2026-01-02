<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Helpers\ScimHelper;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260102115400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Save SCIM token in .env file';
    }

    public function up(Schema $schema): void
    {
        $envFilepath = $this->getEnvFilepath();

        $envFile = file_get_contents($envFilepath);

        if ($token = ScimHelper::createToken()) {
            $newEnvFile = str_replace('{{SCIM_TOKEN}}', $token, $envFile);

            file_put_contents($envFilepath, $newEnvFile);
        }
    }

    public function down(Schema $schema): void {}

    private function getEnvFilepath(): string
    {
        $rootPath = $this->container->getParameter('kernel.project_dir');

        return $rootPath.'/.env';
    }
}
