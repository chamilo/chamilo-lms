<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20251020121000 extends AbstractMigrationChamilo
{
    private const DEBUG = true;

    public function getDescription(): string
    {
        return 'Migrate gradebook certificates from PersonalFile into resource-based storage; delete PersonalFile only after a successful migration.';
    }

    public function up(Schema $schema): void
    {
        /** @var GradebookCertificateRepository $certRepo */
        $certRepo = $this->container->get(GradebookCertificateRepository::class);
        /** @var PersonalFileRepository $personalRepo */
        $personalRepo = $this->container->get(PersonalFileRepository::class);
        /** @var ResourceNodeRepository $rnRepo */
        $rnRepo = $this->container->get(ResourceNodeRepository::class);

        $em = $this->entityManager;

        // missing resource node but having a legacy path.
        $dql = 'SELECT gc
                FROM Chamilo\CoreBundle\Entity\GradebookCertificate gc
                WHERE gc.resourceNode IS NULL
                  AND gc.pathCertificate IS NOT NULL
                  AND gc.pathCertificate <> :empty
                ORDER BY gc.id ASC';

        $q = $em->createQuery($dql)->setParameter('empty', '');

        $migrated = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach ($q->toIterable() as $gc) {
            \assert($gc instanceof GradebookCertificate);

            $user   = $gc->getUser();
            $userId = (int) $user->getId();
            $catId  = $gc->getCategory() ? (int) $gc->getCategory()->getId() : 0;
            $score  = (float) $gc->getScoreCertificate();
            $path   = (string) ($gc->getPathCertificate() ?? '');

            // Find the legacy PersonalFile (robust title search + optional creator-scope search).
            $pf = $this->findLegacyPersonalFile($personalRepo, $gc);
            if (!$pf) {
                $this->dbg(sprintf(
                    '[skip] gc#%d user=%d cat=%d -> legacy PersonalFile not found for "%s"',
                    (int) $gc->getId(), $userId, $catId, $path
                ));
                $skipped++;
                continue;
            }

            // Read the legacy HTML (robust strategy).
            $html = $this->readLegacyHtml($personalRepo, $rnRepo, $pf);
            if (!is_string($html) || $html === '') {
                $this->dbg(sprintf(
                    '[error] gc#%d user=%d cat=%d -> failed to read legacy HTML content from PF (title="%s")',
                    (int) $gc->getId(), $userId, $catId, (string) $pf->getTitle()
                ));
                $errors++;
                continue;
            }

            try {
                // Move to Resource (resource type "files")
                $cert = $certRepo->upsertCertificateResource($catId, $userId, $score, $html, null);

                // Remove legacy PF only after a successful migration
                $em->remove($pf);
                $em->flush();
                $em->clear();

                $migrated++;
                $this->dbg(sprintf(
                    '[ok] gc#%d user=%d cat=%d -> migrated to resource (cert id=%d) and removed PF "%s"',
                    (int) $gc->getId(), $userId, $catId, (int) $cert->getId(), (string) $pf->getTitle()
                ));
            } catch (\Throwable $e) {
                $this->dbg(sprintf(
                    '[error] gc#%d user=%d cat=%d -> upsert failed: %s',
                    (int) $gc->getId(), $userId, $catId, $e->getMessage()
                ));
                $errors++;
                // Do not remove PF on failure
            }
        }

        $summary = sprintf('Summary: migrated=%d skipped=%d errors=%d', $migrated, $skipped, $errors);
        $this->write("\n".$summary."\n");
        $this->dbg($summary);
    }

    /**
     * Run outside a single big transaction so we can flush/clear per item.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    /**
     * Debug helper: send messages to PHP error_log only when DEBUG is enabled.
     */
    private function dbg(string $message): void
    {
        if (self::DEBUG) {
            error_log('[CERT MIGRATION] '.$message);
        }
    }

    /**
     * Try to locate the PersonalFile using common title variants and (if available)
     * a creator-scoped lookup to reduce ambiguity.
     */
    private function findLegacyPersonalFile(
        PersonalFileRepository $personalRepo,
        GradebookCertificate $gc
    ): ?PersonalFile {
        $title = (string) ($gc->getPathCertificate() ?? '');
        if ($title === '') {
            return null;
        }

        $variants = [$title];

        // With and without a leading slash
        if ($title[0] !== '/') {
            $variants[] = '/'.$title;
        }

        // Also try by basename (in case a full path was stored)
        $base = \basename($title);
        if ($base !== $title) {
            $variants[] = $base;
        }

        // Also try with/without ".html"
        $noExt = preg_replace('/\.html$/i', '', $base);
        if ($noExt && $noExt !== $base) {
            $variants[] = $noExt;
        } elseif ($base !== '' && stripos($base, '.html') === false) {
            $variants[] = $base.'.html';
        }

        foreach (array_unique($variants) as $v) {
            $found = $personalRepo->findOneBy(['title' => $v]);
            if ($found instanceof PersonalFile) {
                return $found;
            }
        }

        // search by creator scope if the helper exists
        $user = $gc->getUser();
        if (\method_exists($personalRepo, 'getResourceByCreatorFromTitle')) {
            try {
                $candidate = $personalRepo->getResourceByCreatorFromTitle(
                    $base !== '' ? $base : $title,
                    $user,
                    $user->getResourceNode()
                );
                if ($candidate instanceof PersonalFile) {
                    return $candidate;
                }
            } catch (\Throwable $e) {
                $this->dbg('Creator-scoped PF lookup failed: '.$e->getMessage());
            }
        }

        return null;
    }

    /**
     * Read HTML from PersonalFile
     */
    private function readLegacyHtml(
        PersonalFileRepository $personalRepo,
        ResourceNodeRepository $rnRepo,
        PersonalFile $pf
    ): ?string {
        // Repository helper
        try {
            $content = $personalRepo->getResourceFileContent($pf);
            if (is_string($content) && $content !== '') {
                return $content;
            }
        } catch (\Throwable $e) {
            $this->dbg('[info] PF read via repository failed: '.$e->getMessage());
        }

        // FileSystem paths
        try {
            /** @var ResourceNode|null $node */
            $node = $pf->getResourceNode();
            if ($node) {
                $fs = $rnRepo->getFileSystem();
                if ($fs) {
                    $basePath = rtrim((string) $node->getPath(), '/');

                    $sharded = static function (string $filename): string {
                        $a = $filename[0] ?? '_';
                        $b = $filename[1] ?? '_';
                        $c = $filename[2] ?? '_';
                        return sprintf('resource/%s/%s/%s/%s', $a, $b, $c, $filename);
                    };

                    foreach ($node->getResourceFiles() as $rf) {
                        $candidates = [];

                        $t = (string) $rf->getTitle();
                        $o = (string) $rf->getOriginalName();

                        if ($t !== '') {
                            if ($basePath !== '') {
                                $candidates[] = $basePath.'/'.$t;
                            }
                            $candidates[] = $sharded($t);
                        }
                        if ($o !== '') {
                            if ($basePath !== '') {
                                $candidates[] = $basePath.'/'.$o;
                            }
                            $candidates[] = $sharded($o);
                        }

                        foreach ($candidates as $p) {
                            if ($fs->fileExists($p)) {
                                $data = $fs->read($p);
                                if (is_string($data) && $data !== '') {
                                    return $data;
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->dbg('[info] PF read via filesystem failed: '.$e->getMessage());
        }

        // Final fallback: generic node default content (may still fail)
        try {
            $node = $pf->getResourceNode();
            if ($node) {
                return $rnRepo->getResourceNodeFileContent($node);
            }
        } catch (\Throwable $e) {
            $this->dbg('[info] PF read via node fallback failed: '.$e->getMessage());
        }

        return null;
    }
}
