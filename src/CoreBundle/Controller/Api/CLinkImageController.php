<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CourseBundle\Entity\CLink;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CLinkImageController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(CLink $link, Request $request): Response
    {
        $removeImage = $request->request->getBoolean('removeImage', false);
        $file = $request->files->get('customImage');

        if ($removeImage && $link->getCustomImage()) {
            $this->entityManager->remove($link->getCustomImage());
            $link->setCustomImage(null);
            $this->entityManager->persist($link);
            $this->entityManager->flush();

            if (!$file) {
                return new Response('Image removed successfully', Response::HTTP_OK);
            }
        }

        if (!$file || !$file->isValid()) {
            return new Response('Invalid or missing file', Response::HTTP_BAD_REQUEST);
        }

        try {
            $finalFile = $file;
            $asset = new Asset();
            $asset->setFile($finalFile)
                ->setCategory(Asset::LINK)
                ->setTitle($file->getClientOriginalName())
            ;

            $this->entityManager->persist($asset);
            $this->entityManager->flush();

            $link->setCustomImage($asset);
            $this->entityManager->persist($link);
            $this->entityManager->flush();

            return new Response('Image uploaded and linked successfully', Response::HTTP_OK);
        } catch (Exception $e) {
            return new Response('Error processing image: '.$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
