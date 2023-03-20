<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageAttachment;
use Chamilo\CoreBundle\Repository\Node\MessageAttachmentRepository;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateMessageAttachmentAction extends BaseResourceFileAction
{
    /**
     * @throws Exception
     */
    public function __invoke(Request $request, MessageAttachmentRepository $repo, EntityManager $em): MessageAttachment
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('file');

        if (empty($uploadedFile)) {
            throw new BadRequestHttpException('file is required');
        }

        $messageRepo = $em->getRepository(Message::class);

        $message = $messageRepo->find($request->get('messageId'));

        $attachment = (new MessageAttachment())
            ->setFilename($uploadedFile->getFilename())
            ->setMessage($message)
            ->setParent($message->getSender())
            ->setCreator($message->getSender())
        ;

        foreach ($message->getReceivers() as $receiver) {
            $attachment->addUserLink($receiver->getReceiver());
        }

        $message->addAttachment($attachment);

        $em->persist($attachment);
        $repo->addFile($attachment, $uploadedFile);
        $em->flush();

        return $attachment;
    }
}
