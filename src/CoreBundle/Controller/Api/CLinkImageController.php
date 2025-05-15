<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CourseBundle\Entity\CLink;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use const IMAGETYPE_GIF;
use const IMAGETYPE_JPEG;
use const IMAGETYPE_PNG;

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

        if ($removeImage) {
            if ($link->getCustomImage()) {
                $this->entityManager->remove($link->getCustomImage());
                $link->setCustomImage(null);
                $this->entityManager->persist($link);
                $this->entityManager->flush();

                if (!$file) {
                    return new Response('Image removed successfully', Response::HTTP_OK);
                }
            }
        }

        if (!$file || !$file->isValid()) {
            return new Response('Invalid or missing file', Response::HTTP_BAD_REQUEST);
        }

        try {
            $asset = new Asset();
            $asset->setFile($file)
                ->setCategory(Asset::LINK)
                ->setTitle($file->getClientOriginalName())
            ;

            $this->entityManager->persist($asset);
            $this->entityManager->flush();

            $uploadedFilePath = $file->getPathname();

            $croppedFilePath = $this->cropImage($uploadedFilePath);

            if (!file_exists($croppedFilePath)) {
                @unlink($uploadedFilePath);

                return new Response('Error creating cropped image', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $asset->setFile(new File($croppedFilePath));
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

    private function cropImage(string $filePath): string
    {
        [$originalWidth, $originalHeight, $imageType] = getimagesize($filePath);

        if (!$originalWidth || !$originalHeight) {
            throw new RuntimeException('Invalid image file');
        }

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($filePath);

                break;

            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($filePath);

                break;

            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($filePath);

                break;

            default:
                throw new RuntimeException('Unsupported image type');
        }

        $croppedImage = imagecreatetruecolor(120, 120);

        $cropWidth = min($originalWidth, $originalHeight);
        $cropHeight = $cropWidth;
        $srcX = (int) (($originalWidth - $cropWidth) / 2);
        $srcY = (int) (($originalHeight - $cropHeight) / 2);

        imagecopyresampled(
            $croppedImage,
            $sourceImage,
            0,
            0,
            $srcX,
            $srcY,
            $cropWidth,
            $cropHeight,
            120,
            120
        );

        $croppedFilePath = sys_get_temp_dir().'/'.uniqid('cropped_', true).'.png';
        imagepng($croppedImage, $croppedFilePath);

        imagedestroy($sourceImage);
        imagedestroy($croppedImage);

        return $croppedFilePath;
    }
}
