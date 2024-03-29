<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Repository\CLpCategoryRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Doctrine\DBAL\Schema\Schema;

class Version20240327172830 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create shortcuts for c_lp and c_lp_category published on course home';
    }

    public function up(Schema $schema): void
    {
        // File generated in the Version20180928172830 migration
        $toolLinksContent = $this->readFile('tool_links');

        if (empty($toolLinksContent)) {
            $this->write('tool_links file not found. Exiting.');

            return;
        }

        $toolLinks = unserialize($toolLinksContent);

        $lpRepo = $this->container->get(CLpRepository::class);
        $lpCategoryRepo = $this->container->get(CLpCategoryRepository::class);
        $shortcutRepo = $this->container->get(CShortcutRepository::class);

        foreach ($toolLinks as $toolLink) {
            $url = parse_url($toolLink['link']);
            $query = [];
            parse_str($url['query'] ?? '', $query);

            $admin = $this->getAdmin();
            $course = $this->findCourse($toolLink['c_id']);
            $session = $this->findSession($toolLink['session_id']);
            $resource = null;

            if (str_contains($url['path'], 'lp_controller.php') && isset($query['action'])) {
                if (isset($query['lp_id']) && 'view' === $query['action']) {
                    $resource = $lpRepo->find($query['lp_id']);
                } elseif (isset($query['id']) && 'view_category' === $query['action']) {
                    $resource = $lpCategoryRepo->find($query['id']);
                }
            }

            if ($resource) {
                $shortcut = $shortcutRepo->getShortcutFromResource($resource);

                if ($shortcut) {
                    continue;
                }

                $shortcutRepo->addShortCut($resource, $admin, $course, $session);
            }
        }

        $this->entityManager->flush();

        $this->removeFile('tool_links');
    }
}
