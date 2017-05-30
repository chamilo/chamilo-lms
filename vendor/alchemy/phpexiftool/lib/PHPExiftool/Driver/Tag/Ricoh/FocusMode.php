<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Ricoh;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FocusMode extends AbstractTag
{

    protected $Id = 4102;

    protected $Name = 'FocusMode';

    protected $FullName = 'Ricoh::Main';

    protected $GroupName = 'Ricoh';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Ricoh';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Focus Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Multi AF',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Spot AF',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Snap',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Infinity',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Face Detect',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Subject Tracking',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Pinpoint AF',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Movie',
        ),
    );

}
