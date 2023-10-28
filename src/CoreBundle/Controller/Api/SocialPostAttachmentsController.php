<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Entity\SocialPostAttachment;
use Chamilo\CoreBundle\Repository\Node\SocialPostAttachmentRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

class SocialPostAttachmentsController extends BaseResourceFileAction
{

    public function __invoke(SocialPost $socialPost, EntityManager $em, Security $security, SocialPostAttachmentRepository $attachmentRepo): JsonResponse
    {
        $attachments = $em->getRepository(SocialPostAttachment::class)->findBy(['socialPost' => $socialPost->getId()]);

        $attachmentsInfo = [];
        if ($attachments) {
            foreach ($attachments as $attachment) {
                $attachmentsInfo[] = [
                    'id' => $attachment->getId(),
                    'filename' => $attachment->getFilename(),
                    'path' => $attachmentRepo->getResourceFileUrl($attachment),
                    'size' => $attachment->getSize(),
                ];
            }
        }

        return new JsonResponse($attachmentsInfo, JsonResponse::HTTP_OK);
    }
}
