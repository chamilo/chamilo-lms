<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MPEG;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AspectRatio extends AbstractTag
{

    protected $Id = 'Bit24-27';

    protected $Name = 'AspectRatio';

    protected $FullName = 'MPEG::Video';

    protected $GroupName = 'MPEG';

    protected $g0 = 'MPEG';

    protected $g1 = 'MPEG';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Aspect Ratio';

    protected $Values = array(
        '0.6735' => array(
            'Id' => '0.6735',
            'Label' => '0.6735',
        ),
        '0.7031' => array(
            'Id' => '0.7031',
            'Label' => '16:9, 625 line, PAL',
        ),
        '0.7615' => array(
            'Id' => '0.7615',
            'Label' => '0.7615',
        ),
        '0.8055' => array(
            'Id' => '0.8055',
            'Label' => '0.8055',
        ),
        '0.8437' => array(
            'Id' => '0.8437',
            'Label' => '16:9, 525 line, NTSC',
        ),
        '0.8935' => array(
            'Id' => '0.8935',
            'Label' => '0.8935',
        ),
        '0.9157' => array(
            'Id' => '0.9157',
            'Label' => '4:3, 625 line, PAL, CCIR601',
        ),
        '0.9815' => array(
            'Id' => '0.9815',
            'Label' => '0.9815',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '1:1',
        ),
        '1.095' => array(
            'Id' => '1.095',
            'Label' => '4:3, 525 line, NTSC, CCIR601',
        ),
        '1.0255' => array(
            'Id' => '1.0255',
            'Label' => '1.0255',
        ),
        '1.0695' => array(
            'Id' => '1.0695',
            'Label' => '1.0695',
        ),
        '1.1575' => array(
            'Id' => '1.1575',
            'Label' => '1.1575',
        ),
        '1.2015' => array(
            'Id' => '1.2015',
            'Label' => '1.2015',
        ),
    );

}
