<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioAttachment;
use Chamilo\CoreBundle\Entity\PortfolioComment as PortfolioCommentEntity;
use Chamilo\UserBundle\Entity\User;
use UserManager;
use Xabbuh\XApi\Model\Attachment;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\LanguageMap;

trait PortfolioAttachmentsTrait
{
    /**
     * @param array<int, PortfolioAttachment> $portfolioAttachments
     *
     * @return array<int, Attachment>
     */
    protected function generateAttachments(array $portfolioAttachments, User $user): array
    {
        if (empty($portfolioAttachments)) {
            return [];
        }

        $attachments = [];

        $userDirectory = UserManager::getUserPathById($user->getId(), 'system');
        $attachmentsDirectory = $userDirectory.'portfolio_attachments/';

        $langIso = api_get_language_isocode();

        $cidreq = api_get_cidreq();
        $baseUrl = api_get_path(WEB_CODE_PATH).'portfolio/index.php?'.($cidreq ? $cidreq.'&' : '');

        foreach ($portfolioAttachments as $portfolioAttachment) {
            $attachmentFilename = $attachmentsDirectory.$portfolioAttachment->getPath();

            $display = LanguageMap::create(
                ['und' => $portfolioAttachment->getFilename()]
            );
            $description = null;

            if ($portfolioAttachment->getComment()) {
                $description = LanguageMap::create(
                    [$langIso => $portfolioAttachment->getComment()]
                );
            }

            $attachments[] = new Attachment(
                IRI::fromString('http://id.tincanapi.com/attachment/supporting_media'),
                mime_content_type($attachmentFilename),
                $portfolioAttachment->getSize(),
                hash_file('sha256', $attachmentFilename),
                $display,
                $description,
                IRL::fromString(
                    $baseUrl.http_build_query(['action' => 'download', 'file' => $portfolioAttachment->getPath()])
                )
            );
        }

        return $attachments;
    }

    protected function generateAttachmentsForItem(Portfolio $item): array
    {
        $itemAttachments = \Database::getManager()
            ->getRepository(PortfolioAttachment::class)
            ->findFromItem($item)
        ;

        return $this->generateAttachments($itemAttachments, $item->getUser());
    }

    protected function generateAttachmentsForComment(PortfolioCommentEntity $comment): array
    {
        $commentAttachments = \Database::getManager()
            ->getRepository(PortfolioAttachment::class)
            ->findFromComment($this->comment)
        ;

        return $this->generateAttachments($commentAttachments, $comment->getAuthor());
    }
}
