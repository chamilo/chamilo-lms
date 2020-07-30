<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Samsung;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PictureWizardMode extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'PictureWizardMode';

    protected $FullName = 'Samsung::PictureWizard';

    protected $GroupName = 'Samsung';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Samsung';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Picture Wizard Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Vivid',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Landscape',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Forest',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Retro',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Cool',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Calm',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Classic',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Custom1',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Custom2',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Custom3',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'n/a',
        ),
    );

}
