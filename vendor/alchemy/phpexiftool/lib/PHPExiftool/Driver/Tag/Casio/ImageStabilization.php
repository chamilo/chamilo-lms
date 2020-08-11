<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Casio;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageStabilization extends AbstractTag
{

    protected $Id = 12320;

    protected $Name = 'ImageStabilization';

    protected $FullName = 'Casio::Type2';

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Image Stabilization';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Best Shot',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Movie Anti-Shake',
        ),
        '0 0' => array(
            'Id' => '0 0',
            'Label' => 'Off',
        ),
        '0 1' => array(
            'Id' => '0 1',
            'Label' => 'Off (1)',
        ),
        '0 3' => array(
            'Id' => '0 3',
            'Label' => 'CCD Shift',
        ),
        '2 1' => array(
            'Id' => '2 1',
            'Label' => 'High Sensitivity',
        ),
        '2 3' => array(
            'Id' => '2 3',
            'Label' => 'CCD Shift + High Sensitivity',
        ),
        '16 0' => array(
            'Id' => '16 0',
            'Label' => 'Slow Shutter',
        ),
        '18 0' => array(
            'Id' => '18 0',
            'Label' => 'Anti-Shake',
        ),
        '20 0' => array(
            'Id' => '20 0',
            'Label' => 'High Sensitivity',
        ),
    );

}
