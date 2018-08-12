<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPPlus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageFileFormatAsDelivered extends AbstractTag
{

    protected $Id = 'ImageFileFormatAsDelivered';

    protected $Name = 'ImageFileFormatAsDelivered';

    protected $FullName = 'XMP::plus';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Image File Format As Delivered';

    protected $Values = array(
        'FF-BMP' => array(
            'Id' => 'FF-BMP',
            'Label' => 'Windows Bitmap (BMP)',
        ),
        'FF-DNG' => array(
            'Id' => 'FF-DNG',
            'Label' => 'Digital Negative (DNG)',
        ),
        'FF-EPS' => array(
            'Id' => 'FF-EPS',
            'Label' => 'Encapsulated PostScript (EPS)',
        ),
        'FF-GIF' => array(
            'Id' => 'FF-GIF',
            'Label' => 'Graphics Interchange Format (GIF)',
        ),
        'FF-JPG' => array(
            'Id' => 'FF-JPG',
            'Label' => 'JPEG Interchange Formats (JPG, JIF, JFIF)',
        ),
        'FF-OTR' => array(
            'Id' => 'FF-OTR',
            'Label' => 'Other',
        ),
        'FF-PIC' => array(
            'Id' => 'FF-PIC',
            'Label' => 'Macintosh Picture (PICT)',
        ),
        'FF-PNG' => array(
            'Id' => 'FF-PNG',
            'Label' => 'Portable Network Graphics (PNG)',
        ),
        'FF-PSD' => array(
            'Id' => 'FF-PSD',
            'Label' => 'Photoshop Document (PSD)',
        ),
        'FF-RAW' => array(
            'Id' => 'FF-RAW',
            'Label' => 'Proprietary RAW Image Format',
        ),
        'FF-TIF' => array(
            'Id' => 'FF-TIF',
            'Label' => 'Tagged Image File Format (TIFF)',
        ),
        'FF-WMP' => array(
            'Id' => 'FF-WMP',
            'Label' => 'Windows Media Photo (HD Photo)',
        ),
    );

}
