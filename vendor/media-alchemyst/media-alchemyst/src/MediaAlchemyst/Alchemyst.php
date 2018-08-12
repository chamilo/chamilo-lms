<?php

/*
 * This file is part of Media-Alchemyst.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaAlchemyst;

use MediaVorus\Media\MediaInterface;
use MediaVorus\Exception\FileNotFoundException as MediaVorusFileNotFoundException;
use MediaAlchemyst\Exception\FileNotFoundException;
use MediaAlchemyst\Exception\RuntimeException;
use MediaAlchemyst\Specification\SpecificationInterface;
use MediaAlchemyst\Transmuter\Audio2Audio;
use MediaAlchemyst\Transmuter\Document2Flash;
use MediaAlchemyst\Transmuter\Document2Image;
use MediaAlchemyst\Transmuter\Flash2Image;
use MediaAlchemyst\Transmuter\Image2Image;
use MediaAlchemyst\Transmuter\Video2Animation;
use MediaAlchemyst\Transmuter\Video2Image;
use MediaAlchemyst\Transmuter\Video2Video;
use Neutron\TemporaryFilesystem\Manager;
use Neutron\TemporaryFilesystem\TemporaryFilesystem;
use Symfony\Component\Filesystem\Filesystem;

class Alchemyst
{
    /** @var DriversContainer */
    private $drivers;
    /** @var Manager */
    private $tmpFileManager;

    public function __construct(DriversContainer $container, Manager $manager)
    {
        $this->drivers = $container;
        $this->tmpFileManager = $manager;
    }

    public function getDrivers()
    {
        return $this->drivers;
    }

    public function turnInto($source, $pathfile_dest, SpecificationInterface $specs)
    {
        try {
            $mediafile = $this->drivers['mediavorus']->guess($source);
        } catch (MediaVorusFileNotFoundException $e) {
            throw new FileNotFoundException(sprintf('File %s not found', $source));
        }

        $this->routeAction($mediafile, $pathfile_dest, $specs);

        return $this;
    }

    public static function create()
    {
        $fs = new Filesystem();

        return new static(DriversContainer::create(), new Manager(new TemporaryFilesystem($fs), $fs));
    }

    private function routeAction($mediafile, $pathfile_dest, SpecificationInterface $specs)
    {
        $route = sprintf('%s-%s', $mediafile->getType(), $specs->getType());

        switch ($route) {
            case sprintf('%s-%s', MediaInterface::TYPE_AUDIO, SpecificationInterface::TYPE_IMAGE):
                throw new RuntimeException('No transmuter available... Please join the community to implement it !');
                break;
            case sprintf('%s-%s', MediaInterface::TYPE_AUDIO, SpecificationInterface::TYPE_VIDEO):
                throw new RuntimeException('No transmuter available... Please join the community to implement it !');
                break;
            case sprintf('%s-%s', MediaInterface::TYPE_AUDIO, SpecificationInterface::TYPE_AUDIO):
                $transmuter = new Audio2Audio($this->drivers, $this->tmpFileManager);
                break;

            case sprintf('%s-%s', MediaInterface::TYPE_FLASH, SpecificationInterface::TYPE_IMAGE):
                $transmuter = new Flash2Image($this->drivers, $this->tmpFileManager);
                break;

            case sprintf('%s-%s', MediaInterface::TYPE_DOCUMENT, SpecificationInterface::TYPE_IMAGE):
                $transmuter = new Document2Image($this->drivers, $this->tmpFileManager);
                break;
            case sprintf('%s-%s', MediaInterface::TYPE_DOCUMENT, SpecificationInterface::TYPE_SWF):
                $transmuter = new Document2Flash($this->drivers, $this->tmpFileManager);
                break;

            case sprintf('%s-%s', MediaInterface::TYPE_IMAGE, SpecificationInterface::TYPE_IMAGE):
                $transmuter = new Image2Image($this->drivers, $this->tmpFileManager);
                break;

            case sprintf('%s-%s', MediaInterface::TYPE_VIDEO, SpecificationInterface::TYPE_IMAGE):
                $transmuter = new Video2Image($this->drivers, $this->tmpFileManager);
                break;
            case sprintf('%s-%s', MediaInterface::TYPE_VIDEO, SpecificationInterface::TYPE_ANIMATION):
                $transmuter = new Video2Animation($this->drivers, $this->tmpFileManager);
                break;
            case sprintf('%s-%s', MediaInterface::TYPE_VIDEO, SpecificationInterface::TYPE_VIDEO):
                $transmuter = new Video2Video($this->drivers, $this->tmpFileManager);
                break;
            default:
                throw new RuntimeException(sprintf('No transmuter available for `%s`. Please join the community to implement it !', $route));
                break;
        }

        $transmuter->execute($specs, $mediafile, $pathfile_dest);

        return $this;
    }
}
