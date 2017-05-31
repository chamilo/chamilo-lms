<?php

/*
 * This file is part of Media-Alchemyst.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaAlchemyst\Transmuter;

use Ghostscript\Exception\ExceptionInterface as GhostscriptException;
use Imagine\Exception\Exception as ImagineException;
use Imagine\Image\ImageInterface;
use MediaVorus\Exception\ExceptionInterface as MediaVorusException;
use MediaAlchemyst\Specification\Image;
use MediaAlchemyst\Specification\SpecificationInterface;
use MediaAlchemyst\Exception\SpecNotSupportedException;
use MediaAlchemyst\Exception\RuntimeException;
use MediaVorus\Media\MediaInterface;
use Unoconv\Unoconv;
use Unoconv\Exception\ExceptionInterface as UnoconvException;

class Document2Image extends AbstractTransmuter
{
    public function execute(SpecificationInterface $spec, MediaInterface $source, $dest)
    {
        if (! $spec instanceof Image) {
            throw new SpecNotSupportedException('Document2Image only accept Image specs');
        }

        $tmpDest = $this->tmpFileManager->createTemporaryFile(self::TMP_FILE_SCOPE, 'unoconv');

        try {
            if ($source->getFile()->getMimeType() != 'application/pdf') {
                $this->container['unoconv']->transcode(
                    $source->getFile()->getPathname(), Unoconv::FORMAT_PDF, $tmpDest, '1-1'
                );
            } else {
                copy($source->getFile()->getPathname(), $tmpDest);
            }

            $tmpDestSinglePage = $this->tmpFileManager->createTemporaryFile(self::TMP_FILE_SCOPE, 'unoconv-single');

            $this->container['ghostscript.transcoder']->toPDF(
                $tmpDest, $tmpDestSinglePage, 1, 1
            );

            $image = $this->container['imagine']->open($tmpDestSinglePage);

            $options = array(
                'quality'          => $spec->getQuality(),
                'resolution-units' => $spec->getResolutionUnit(),
                'resolution-x'     => $spec->getResolutionX(),
                'resolution-y'     => $spec->getResolutionY(),
//                'flatten'          => $spec->isFlatten(),
                'disable-alpha'    => $spec->isFlatten(),
            );

            if ($spec->getWidth() && $spec->getHeight()) {
                $box = $this->boxFromSize($spec, $image->getSize()->getWidth(), $image->getSize()->getHeight());

                if (null !== $box) {
                    if ($spec->getResizeMode() == Image::RESIZE_MODE_OUTBOUND) {
                        /* @var $image \Imagine\Gmagick\Image */
                        $image = $image->thumbnail($box, ImageInterface::THUMBNAIL_OUTBOUND);
                    } else {
                        $image = $image->resize($box);
                    }
                }
            }

            $image->save($dest, $options);

            unset($image);

            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
        } catch (GhostscriptException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        } catch (UnoconvException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw new RuntimeException('Unable to transmute document to image due to Unoconv', null, $e);
        } catch (ImagineException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw new RuntimeException('Unable to transmute document to image due to Imagine', null, $e);
        } catch (MediaVorusException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw new RuntimeException('Unable to transmute document to image due to MediaVorus', null, $e);
        } catch (RuntimeException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw $e;
        }
    }
}
