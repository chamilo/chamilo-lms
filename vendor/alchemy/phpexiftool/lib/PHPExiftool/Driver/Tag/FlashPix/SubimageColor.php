<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FlashPix;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SubimageColor extends AbstractTag
{

    protected $Id = 33554434;

    protected $Name = 'SubimageColor';

    protected $FullName = 'FlashPix::Image';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Subimage Color';

    protected $Values = array(
        '01 0000' => array(
            'Id' => '01 0000',
            'Label' => 'Opacity Only',
        ),
        '01 0001' => array(
            'Id' => '01 0001',
            'Label' => 'Monochrome',
        ),
        '01 8000' => array(
            'Id' => '01 8000',
            'Label' => 'Opacity Only (uncalibrated)',
        ),
        '01 8001' => array(
            'Id' => '01 8001',
            'Label' => 'Monochrome (uncalibrated)',
        ),
        '03 0002' => array(
            'Id' => '03 0002',
            'Label' => 'YCbCr',
        ),
        '03 0003' => array(
            'Id' => '03 0003',
            'Label' => 'RGB',
        ),
        '03 8002' => array(
            'Id' => '03 8002',
            'Label' => 'YCbCr (uncalibrated)',
        ),
        '03 8003' => array(
            'Id' => '03 8003',
            'Label' => 'RGB (uncalibrated)',
        ),
        '04 0002' => array(
            'Id' => '04 0002',
            'Label' => 'YCbCr with Opacity',
        ),
        '04 0003' => array(
            'Id' => '04 0003',
            'Label' => 'RGB with Opacity',
        ),
        '04 8002' => array(
            'Id' => '04 8002',
            'Label' => 'YCbCr with Opacity (uncalibrated)',
        ),
        '04 8003' => array(
            'Id' => '04 8003',
            'Label' => 'RGB with Opacity (uncalibrated)',
        ),
    );

}
