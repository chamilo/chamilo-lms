<?php

/*
 * This file is part of PHP-SwfTools.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwfTools\Processor;

use Alchemy\BinaryDriver\Exception\ExecutableNotFoundException;
use SwfTools\EmbeddedObject;
use SwfTools\Exception\InvalidArgumentException;
use SwfTools\Exception\RuntimeException;

class FlashFile extends File
{
    /**
     * Render the flash to PNG file
     *
     * @param  string                   $inputFile
     * @param  string                   $outputFile
     * @param  Boolean                  $legacy_rendering
     * @return Boolean
     * @throws InvalidArgumentException
     */
    public function render($inputFile, $outputFile, $legacy_rendering = false)
    {
        if (!$outputFile) {
            throw new InvalidArgumentException('Invalid argument');
        }

        $outputFile = static::changePathnameExtension($outputFile, 'png');

        try {
            $this->container['swfrender']->render($inputFile, $outputFile, $legacy_rendering);
        } catch (ExecutableNotFoundException $e) {
            throw new RuntimeException('Unable to load swfrender', $e->getCode(), $e);
        }

        return $outputFile;
    }

    /**
     * List all embedded object of the current flash file
     *
     * @param  string           $inputFile
     * @return type
     * @throws RuntimeException
     */
    public function listEmbeddedObjects($inputFile)
    {
        $embedded = array();

        try {
            $datas = explode("\n", $this->container['swfextract']->listEmbedded($inputFile));
        } catch (ExecutableNotFoundException $e) {
            throw new RuntimeException('Unable to load swfextract', $e->getCode(), $e);
        }

        foreach ($datas as $line) {
            $matches = array();

            preg_match('/\[-([a-z]{1})\]\ [0-9]+\ ([a-z]+):\ ID\(s\)\ ([0-9-,\ ]+)/i', $line, $matches);

            if (count($matches) === 0)
                continue;

            $option = $matches[1];
            $type = EmbeddedObject::detectType($matches[2]);

            if (!$type) {
                continue;
            }

            foreach (explode(",", $matches[3]) as $id) {
                if (false !== $offset = strpos($id, '-')) {
                    for ($i = substr($id, 0, $offset); $i <= substr($id, $offset + 1); $i++) {
                        $embedded[] = new EmbeddedObject($option, $matches[2], $i);
                    }
                } else {
                    $embedded[] = new EmbeddedObject($option, $type, $id);
                }
            }
        }

        return $embedded;
    }

    /**
     * Extract the specified Embedded file
     *
     * @param integer $id
     * @param string  $inputFile
     * @param string  $outputFile
     *
     * @return string
     *
     * @throws RuntimeException
     */
    public function extractEmbedded($id, $inputFile, $outputFile)
    {
        if (!$outputFile) {
            throw new InvalidArgumentException('Bad destination');
        }

        foreach ($this->listEmbeddedObjects($inputFile) as $embedded) {
            if ($embedded->getId() == $id) {
                try {
                    $this->container['swfextract']->extract($inputFile, $embedded, $outputFile);
                } catch (ExecutableNotFoundException $e) {
                    throw new RuntimeException('Unable to load swfextract', $e->getCode(), $e);
                }

                return $outputFile;
            }
        }

        throw new RuntimeException('Unable to extract an embedded object');
    }

    /**
     * Extract the first embedded image found
     *
     * @param string $inputFile
     * @param string $outputFile
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function extractFirstImage($inputFile, $outputFile)
    {
        if (!$outputFile) {
            throw new InvalidArgumentException('Bad destination');
        }

        $objects = $this->listEmbeddedObjects($inputFile);

        if (!$objects) {
            throw new RuntimeException('Unable to extract an image');
        }

        foreach ($objects as $embedded) {
            if (in_array($embedded->getType(), array(EmbeddedObject::TYPE_JPEG, EmbeddedObject::TYPE_PNG))) {
                switch ($embedded->getType()) {
                    case EmbeddedObject::TYPE_JPEG:
                        $outputFile = static::changePathnameExtension($outputFile, 'jpg');
                        break;
                    case EmbeddedObject::TYPE_PNG:
                        $outputFile = static::changePathnameExtension($outputFile, 'png');
                        break;
                    default:
                        continue;
                }

                try {
                    $this->container['swfextract']->extract($inputFile, $embedded, $outputFile);
                } catch (ExecutableNotFoundException $e) {
                    throw new RuntimeException('Unable to load swfextract', $e->getCode(), $e);
                }

                return $outputFile;
            }
        }

        throw new RuntimeException('Unable to extract an image');
    }
}
