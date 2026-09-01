<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\DataFixtures\SystemTemplateFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

use const PHP_SAPI;

final class Version20260828190000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Install the bundled Chamilo 3 system document templates, including the default Gradebook certificate.';
    }

    public function up(Schema $schema): void
    {
        if ($this->isSimulationMode()) {
            $this->write('Skipping system template installation during migration simulation.');

            return;
        }

        if (null === $this->container || null === $this->entityManager) {
            throw new RuntimeException('The Symfony container and EntityManager are required to install system templates.');
        }

        if (!$this->container->has(SystemTemplateFixtures::class)) {
            throw new RuntimeException('SystemTemplateFixtures service is not available.');
        }

        $fixtures = $this->container->get(SystemTemplateFixtures::class);
        if (!$fixtures instanceof SystemTemplateFixtures) {
            throw new RuntimeException('Could not resolve SystemTemplateFixtures service.');
        }

        $created = $fixtures->installDefaultTemplates($this->entityManager);

        $this->write(\sprintf(
            'Installed %d bundled system document template(s). Existing templates with the same titles were left unchanged.',
            $created,
        ));
    }

    public function down(Schema $schema): void
    {
        // Do not delete templates during rollback. Administrators may already have
        // customized or used them to create course documents and certificates.
        $this->write('Bundled system templates are intentionally not removed on rollback.');
    }

    private function isSimulationMode(): bool
    {
        if ('cli' !== PHP_SAPI) {
            return false;
        }

        foreach ($_SERVER['argv'] ?? [] as $argument) {
            $argument = (string) $argument;

            if ('--dry-run' === $argument || str_starts_with($argument, '--write-sql')) {
                return true;
            }
        }

        return false;
    }
}
