<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Entity\SocialPostAttachment;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\SocialPostAttachmentRepository;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class CreateSocialPostAttachmentAction extends BaseResourceFileAction
{
    /**
     * @throws Exception
     */
    public function __invoke(Request $request, SocialPostAttachmentRepository $repo, EntityManager $em, Security $security): SocialPostAttachment
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('file');
        $socialPostId = $request->request->get('messageId');

        if (!$uploadedFile instanceof UploadedFile) {
            throw new \Exception('No file uploaded');
        }

        $socialPost = $em->getRepository(SocialPost::class)->find($socialPostId);
        if (!$socialPost) {
            throw new \Exception('No social post found');
        }

        /** @var User $currentUser */
        $currentUser = $security->getUser();
        $attachment = new SocialPostAttachment();
        $attachment->setSocialPost($socialPost);
        $attachment->setPath(uniqid('social_post', true));
        $attachment->setFilename($uploadedFile->getClientOriginalName());
        $attachment->setSize($uploadedFile->getSize());
        $attachment->setInsertUserId($currentUser->getId());
        $attachment->setInsertDateTime(new \DateTime('now', new \DateTimeZone('UTC')));
        $attachment->setParent($currentUser);
        $attachment->addUserLink($currentUser);

        $em->persist($attachment);
        $em->flush();

        $repo->addFile($attachment, $uploadedFile);

        return $attachment;
    }
}
