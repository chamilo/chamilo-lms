<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\VichUploader;

use Chamilo\CoreBundle\Entity\Asset;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

use const PATHINFO_EXTENSION;

/**
 * @implements NamerInterface<Asset>
 */
class AssetFileNameNamer implements NamerInterface
{
    private RequestStack $requestStack;
    private TranslatorInterface $translator;

    public function __construct(RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    public function name($object, PropertyMapping $mapping): string
    {
        if (!$object instanceof Asset) {
            throw new InvalidArgumentException('Expected object of type Asset.');
        }

        $category = $object->getCategory();

        if (\in_array($category, [Asset::TEMPLATE, Asset::SYSTEM_TEMPLATE], true)) {
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $templateId = $object->getId();
                $templateTitle = (string) $request->get('title', 'default-title');
                $titleSlug = $this->slugify($templateTitle);

                $currentFileName = $mapping->getFileName($object);
                $extension = '';

                if (\is_string($currentFileName) && '' !== $currentFileName) {
                    $extension = (string) pathinfo($currentFileName, PATHINFO_EXTENSION);
                }

                if ('' === $extension) {
                    $file = $mapping->getFile($object);
                    if ($file instanceof UploadedFile) {
                        $guessed = $file->guessExtension();
                        $clientExt = $file->getClientOriginalExtension();
                        $extension = (string) ($guessed ?: ($clientExt ?: 'png'));
                    } else {
                        $extension = 'png';
                    }
                }

                // Avoid empty templateId on new entities (id might be null before flush).
                $templateIdSafe = null !== $templateId ? (string) $templateId : 'template';

                return \sprintf('%s-%s.%s', $templateIdSafe, $titleSlug, $extension);
            }
        }

        // Default behavior: keep the current stored filename if available.
        $existing = $mapping->getFileName($object);
        if (\is_string($existing) && '' !== $existing) {
            return $existing;
        }

        $file = $mapping->getFile($object);
        $extension = 'png';

        if ($file instanceof UploadedFile) {
            $guessed = $file->guessExtension();
            $clientExt = $file->getClientOriginalExtension();
            $extension = (string) ($guessed ?: ($clientExt ?: 'png'));
        }

        $random = bin2hex(random_bytes(8));

        return \sprintf('asset-%s.%s', $random, $extension);
    }

    private function slugify(string $text): string
    {
        $text = trim($text);
        if ('' === $text) {
            return 'default-title';
        }

        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $text);
        if (null === $slug) {
            return 'default-title';
        }

        $slug = strtolower(trim($slug, '-'));

        return '' !== $slug ? $slug : 'default-title';
    }
}
