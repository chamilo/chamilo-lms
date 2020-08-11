<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageEditing extends AbstractTag
{

    protected $Id = 50;

    protected $Name = 'ImageEditing';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Image Editing';

    protected $flag_Permanent = true;

    protected $MaxLength = 4;

    protected $Values = array(
        '0 0' => array(
            'Id' => '0 0',
            'Label' => 'None',
        ),
        '0 0 0 0' => array(
            'Id' => '0 0 0 0',
            'Label' => 'None',
        ),
        '0 0 0 4' => array(
            'Id' => '0 0 0 4',
            'Label' => 'Digital Filter',
        ),
        '1 0 0 0' => array(
            'Id' => '1 0 0 0',
            'Label' => 'Resized',
        ),
        '2 0 0 0' => array(
            'Id' => '2 0 0 0',
            'Label' => 'Cropped',
        ),
        '4 0 0 0' => array(
            'Id' => '4 0 0 0',
            'Label' => 'Digital Filter 4',
        ),
        '6 0 0 0' => array(
            'Id' => '6 0 0 0',
            'Label' => 'Digital Filter 6',
        ),
        '8 0 0 0' => array(
            'Id' => '8 0 0 0',
            'Label' => 'Red-eye Correction',
        ),
        '16 0 0 0' => array(
            'Id' => '16 0 0 0',
            'Label' => 'Frame Synthesis?',
        ),
    );

}
