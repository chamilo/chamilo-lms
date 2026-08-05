<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateResourceFileAction
{
    public function __invoke(Request $request, EntityManagerInterface $em): ResourceFile
    {
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }

        $resourceFile = new ResourceFile();
        $resourceFile->setTitle($uploadedFile->getFilename());
        $resourceFile->setFile($uploadedFile);

        if ($request->request->has('language')) {
            $resourceFile->setLanguage($this->findLanguageFromRequest($request, $em));
        }

        return $resourceFile;
    }

    private function findLanguageFromRequest(Request $request, EntityManagerInterface $em): ?Language
    {
        $languageCode = trim((string) $request->request->get('language', ''));
        if ('' === $languageCode) {
            return null;
        }

        if (preg_match('#/api/languages/(\d+)#', $languageCode, $matches)) {
            $language = $em->getRepository(Language::class)->find((int) $matches[1]);

            if ($language instanceof Language) {
                return $language;
            }

            throw new BadRequestHttpException('Invalid resource language.');
        }

        if (!preg_match('/^[a-zA-Z0-9_-]{1,8}$/', $languageCode)) {
            throw new BadRequestHttpException('Invalid resource language.');
        }

        $language = $em->getRepository(Language::class)->findOneBy([
            'isocode' => $languageCode,
            'available' => true,
        ]);

        if ($language instanceof Language) {
            return $language;
        }

        throw new BadRequestHttpException('Invalid resource language.');
    }
}
