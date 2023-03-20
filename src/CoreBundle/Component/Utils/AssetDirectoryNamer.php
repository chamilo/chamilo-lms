<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Utils;

use Chamilo\CoreBundle\Entity\Asset;
use InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\ConfigurableInterface;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

class AssetDirectoryNamer implements DirectoryNamerInterface, ConfigurableInterface
{
    protected PropertyAccessorInterface $propertyAccessor;
    private string $propertyPath;
    private int $charsPerDir = 2;
    private int $dirs = 1;

    public function __construct(?PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param array $options Options for this namer. The following options are accepted:
     *                       - chars_per_dir: how many chars use for each dir.
     *                       - dirs: how many dirs create
     */
    public function configure(array $options): void
    {
        if (empty($options['property'])) {
            throw new InvalidArgumentException('Option "property" is missing or empty.');
        }

        $this->propertyPath = $options['property'];

        $options = array_merge([
            'chars_per_dir' => $this->charsPerDir,
            'dirs' => $this->dirs,
        ], $options);

        $this->charsPerDir = $options['chars_per_dir'];
        $this->dirs = $options['dirs'];
    }

    public function directoryName($object, PropertyMapping $mapping): string
    {
        $fileName = $mapping->getFileName($object);
        $category = $this->propertyAccessor->getValue($object, $this->propertyPath);

        $parts[] = $category;

        if (Asset::EXTRA_FIELD === $category) {
            for ($i = 0, $start = 0; $i < $this->dirs; $i++, $start += $this->charsPerDir) {
                $parts[] = substr($fileName, $start, $this->charsPerDir);
            }
        } else {
            $parts[] = $fileName;
        }

        return implode('/', $parts);
    }
}
