<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioAttachment;
use Chamilo\CoreBundle\Entity\PortfolioComment as PortfolioCommentEntity;
use Chamilo\CoreBundle\Entity\User;
use Database;
use UserManager;

trait PortfolioAttachmentsTrait
{
    /**
     * @param array<int, PortfolioAttachment> $portfolioAttachments
     *
     * @return array<int, array<string, mixed>>
     */
    protected function generateAttachments(array $portfolioAttachments, User $user): array
    {
        if (empty($portfolioAttachments)) {
            return [];
        }

        $attachments = [];

        $userDirectory = UserManager::getUserPathById($user->getId(), 'system');
        $attachmentsDirectory = $userDirectory.'portfolio_attachments/';

        $languageSource = function_exists('api_get_interface_language')
            ? api_get_interface_language()
            : api_get_setting('platformLanguage');

        $langIso = !empty($languageSource)
            ? api_get_language_isocode($languageSource)
            : 'en';

        $cidreq = api_get_cidreq();
        $baseUrl = api_get_path(WEB_CODE_PATH).'portfolio/index.php?'.($cidreq ? $cidreq.'&' : '');

        foreach ($portfolioAttachments as $portfolioAttachment) {
            $attachmentFilename = $attachmentsDirectory.$portfolioAttachment->getPath();

            if (!is_file($attachmentFilename)) {
                continue;
            }

            $attachment = [
                'usageType' => 'http://id.tincanapi.com/attachment/supporting_media',
                'contentType' => mime_content_type($attachmentFilename) ?: 'application/octet-stream',
                'length' => (int) $portfolioAttachment->getSize(),
                'sha2' => hash_file('sha256', $attachmentFilename),
                'display' => [
                    'und' => (string) $portfolioAttachment->getFilename(),
                ],
                'fileUrl' => $baseUrl.http_build_query(
                        ['action' => 'download', 'file' => $portfolioAttachment->getPath()],
                        '',
                        '&',
                        PHP_QUERY_RFC3986
                    ),
            ];

            if ($portfolioAttachment->getComment()) {
                $attachment['description'] = [
                    $langIso => (string) $portfolioAttachment->getComment(),
                ];
            }

            $attachments[] = $attachment;
        }

        return $attachments;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function generateAttachmentsForItem(Portfolio $item): array
    {
        $itemAttachments = Database::getManager()
            ->getRepository(PortfolioAttachment::class)
            ->findFromItem($item);

        return $this->generateAttachments($itemAttachments, $item->getUser());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function generateAttachmentsForComment(PortfolioCommentEntity $comment): array
    {
        $commentAttachments = Database::getManager()
            ->getRepository(PortfolioAttachment::class)
            ->findFromComment($comment);

        return $this->generateAttachments($commentAttachments, $comment->getAuthor());
    }
}
