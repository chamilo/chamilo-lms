<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AddVariantResourceFileAction
{
    public function __invoke(Request $request, EntityManagerInterface $em): ResourceFile
    {
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }

        $resourceNodeId = $request->get('resourceNodeId');
        if (!$resourceNodeId) {
            throw new BadRequestHttpException('"resourceNodeId" is required');
        }

        $resourceNode = $em->getRepository(ResourceNode::class)->find($resourceNodeId);
        if (!$resourceNode) {
            throw new NotFoundHttpException('ResourceNode not found');
        }

        $accessUrlId = $request->get('accessUrlId');
        $accessUrl = null;
        if ($accessUrlId) {
            $accessUrl = $em->getRepository(AccessUrl::class)->find($accessUrlId);
            if (!$accessUrl) {
                throw new NotFoundHttpException('AccessUrl not found');
            }
        }

        $existingResourceFile = $em->getRepository(ResourceFile::class)->findOneBy([
            'resourceNode' => $resourceNode,
            'accessUrl' => $accessUrl,
        ]);

        if ($existingResourceFile) {
            $existingResourceFile->setTitle($uploadedFile->getClientOriginalName());
            $existingResourceFile->setFile($uploadedFile);
            $existingResourceFile->setUpdatedAt(\DateTime::createFromImmutable(new \DateTimeImmutable()));
            $resourceFile = $existingResourceFile;
        } else {
            $resourceFile = new ResourceFile();
            $resourceFile->setTitle($uploadedFile->getClientOriginalName());
            $resourceFile->setFile($uploadedFile);
            $resourceFile->setResourceNode($resourceNode);

            if ($accessUrl) {
                $resourceFile->setAccessUrl($accessUrl);
            }
        }

        $em->persist($resourceFile);
        $em->flush();

        return $resourceFile;
    }
}
