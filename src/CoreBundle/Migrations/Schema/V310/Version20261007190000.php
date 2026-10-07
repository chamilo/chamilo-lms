<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261007190000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Point each blog attachment path to its own resource file instead of the first file of the blog.';
    }

    public function up(Schema $schema): void
    {
        $attachments = $this->connection->fetchAllAssociative(
            "SELECT a.iid, a.filename, a.size, a.path, b.resource_node_id
            FROM c_blog_attachment a
            INNER JOIN c_blog b ON b.iid = a.blog_id
            WHERE a.path LIKE '%/r/blog/blog/%/view'
            ORDER BY a.iid"
        );

        if (empty($attachments)) {
            return;
        }

        // Files already referenced by a fixed path must not be assigned again.
        $usedFileIds = [];
        $fixedPaths = $this->connection->fetchFirstColumn(
            "SELECT path FROM c_blog_attachment WHERE path LIKE '%?resourceFileId=%'"
        );

        foreach ($fixedPaths as $path) {
            if (preg_match('~resourceFileId=(\d+)~', (string) $path, $m)) {
                $usedFileIds[(int) $m[1]] = true;
            }
        }

        $filesByNode = [];

        foreach ($attachments as $attachment) {
            $nodeId = (int) $attachment['resource_node_id'];

            $filesByNode[$nodeId] ??= $this->connection->fetchAllAssociative(
                'SELECT id, original_name, size FROM resource_file WHERE resource_node_id = ? ORDER BY id',
                [$nodeId]
            );

            // The upload created the attachment right after its file, so the first unused
            // file with the same name and size is the one of this attachment.
            $fileId = null;

            foreach ($filesByNode[$nodeId] as $file) {
                $id = (int) $file['id'];

                if (isset($usedFileIds[$id])
                    || (int) $file['size'] !== (int) $attachment['size']
                    || !self::isSameUpload((string) $attachment['filename'], (string) $file['original_name'])
                ) {
                    continue;
                }

                $fileId = $id;

                break;
            }

            if (null === $fileId) {
                $this->write('No resource file found for blog attachment '.$attachment['iid']);

                continue;
            }

            $usedFileIds[$fileId] = true;

            $this->addSql(
                'UPDATE c_blog_attachment SET path = ? WHERE iid = ?',
                [$attachment['path'].'?resourceFileId='.$fileId, (int) $attachment['iid']]
            );
        }
    }

    public function down(Schema $schema): void {}

    /**
     * The upload renamed a repeated name to "<name>_<n>.<ext>" (CreateBlogAttachmentAction).
     */
    private static function isSameUpload(string $filename, string $originalName): bool
    {
        if ($filename === $originalName) {
            return true;
        }

        $info = pathinfo($originalName);
        $extension = isset($info['extension']) ? '.'.$info['extension'] : '';

        return 1 === preg_match(
            '~^'.preg_quote($info['filename'], '~').'_\d+'.preg_quote($extension, '~').'$~',
            $filename
        );
    }
}
