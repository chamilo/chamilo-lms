<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\VichUploader;

use Chamilo\CoreBundle\Entity\Asset;
use InvalidArgumentException;
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

        if (\in_array($category, [Asset::TEMPLATE, Asset::SYSTEM_TEMPLATE])) {
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $templateId = $object->getId();
                $templateTitle = $request->get('title', 'default-title');
                $titleSlug = $this->slugify($templateTitle);
                $extension = pathinfo($mapping->getFileName($object), PATHINFO_EXTENSION);

                return \sprintf('%s-%s.%s', $templateId, $titleSlug, $extension);
            }
        }

        return $mapping->getFileName($object);
    }

    private function slugify(string $text): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
    }
}
