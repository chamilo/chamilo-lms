<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FilterEffectAuto extends AbstractTag
{

    protected $Id = 160;

    protected $Name = 'FilterEffectAuto';

    protected $FullName = 'Canon::PSInfo2';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'Filter Effect Auto';

    protected $flag_Permanent = true;

    protected $Values = array(
        '-559038737' => array(
            'Id' => '-559038737',
            'Label' => 'n/a',
        ),
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Yellow',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Orange',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Red',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Green',
        ),
    );

}
