<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\Entity\PageCategory;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250331202800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrates registration introduction pages from Chamilo 1 (custom HTML files) into the CMS Page entity.';
    }

    public function up(Schema $schema): void
    {
        $accessUrlRepo = $this->entityManager->getRepository(AccessUrl::class);
        $pageCategoryRepo = $this->entityManager->getRepository(PageCategory::class);
        $pageRepo = $this->entityManager->getRepository(Page::class);
        $adminUser = $this->getAdmin();

        // Source directory where Chamilo 1 custom pages are located
        $sourcePath = $this->getUpdateRootPath().'/app/home';
        error_log("[MIGRATION] Looking for registration HTML files in: $sourcePath");

        // Get or create the "introduction" category
        $category = $pageCategoryRepo->findOneBy(['title' => 'introduction']);
        if (!$category) {
            $category = new PageCategory();
            $category
                ->setTitle('introduction')
                ->setType('cms')
                ->setCreator($adminUser);
            $this->entityManager->persist($category);
            $this->entityManager->flush();
            error_log('[MIGRATION] Created "introduction" category.');
        }

        // Loop through directories like /app/home/localhost/
        $accessUrls = scandir($sourcePath);
        foreach ($accessUrls as $dirName) {
            if (in_array($dirName, ['.', '..'])) {
                continue;
            }

            $dirPath = $sourcePath.'/'.$dirName;
            if (!is_dir($dirPath)) {
                continue;
            }

            error_log("[MIGRATION] Checking directory: $dirName");

            // Look for files like register_top_spanish.html
            foreach (glob($dirPath.'/register_top_*.html') as $filePath) {
                $matches = [];
                if (!preg_match('/register_top_(.+)\.html$/', basename($filePath), $matches)) {
                    error_log("[MIGRATION] File name does not match expected pattern: $filePath");
                    continue;
                }

                $locale = $matches[1];

                // Try to find AccessUrl with both http/https and with/without trailing slash
                $normalizedUrls = [
                    'http://' . $dirName . '/',
                    'https://' . $dirName . '/',
                    'http://' . $dirName,
                    'https://' . $dirName,
                ];

                $accessUrl = null;
                foreach ($normalizedUrls as $url) {
                    $accessUrl = $accessUrlRepo->findOneBy(['url' => $url]);
                    if ($accessUrl) {
                        break;
                    }
                }

                if (!$accessUrl) {
                    error_log("[MIGRATION] AccessUrl not found for http(s)://$dirName with or without trailing slash");
                    continue;
                }

                // Skip if page already exists
                $existingPage = $pageRepo->findOneBy([
                    'category' => $category,
                    'url' => $accessUrl,
                    'locale' => $locale,
                ]);

                if ($existingPage) {
                    error_log("[MIGRATION] Page already exists for URL=$accessUrl->getUrl(), locale=$locale. Skipped.");
                    continue;
                }

                // Read file content
                $content = file_get_contents($filePath);
                if (empty($content)) {
                    error_log("[MIGRATION] File is empty: $filePath");
                    continue;
                }

                // Create new Page entity
                $page = new Page();
                $page
                    ->setTitle('Intro inscription')
                    ->setSlug('intro-inscription')
                    ->setContent($content)
                    ->setLocale($locale)
                    ->setCategory($category)
                    ->setEnabled(true)
                    ->setCreator($adminUser)
                    ->setUrl($accessUrl)
                    ->setPosition(1);

                $this->entityManager->persist($page);

                error_log("[MIGRATION] Page created for: URL={$accessUrl->getUrl()}, locale=$locale");
            }
        }

        $this->entityManager->flush();
        error_log('[MIGRATION] Migration completed successfully.');
    }

    public function down(Schema $schema): void {}
}
