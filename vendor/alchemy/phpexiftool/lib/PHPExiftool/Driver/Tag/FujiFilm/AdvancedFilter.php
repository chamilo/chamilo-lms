<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FujiFilm;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AdvancedFilter extends AbstractTag
{

    protected $Id = 4609;

    protected $Name = 'AdvancedFilter';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Advanced Filter';

    protected $flag_Permanent = true;

    protected $Values = array(
        65536 => array(
            'Id' => 65536,
            'Label' => 'Pop Color',
        ),
        131072 => array(
            'Id' => 131072,
            'Label' => 'Hi Key',
        ),
        196608 => array(
            'Id' => 196608,
            'Label' => 'Toy Camera',
        ),
        262144 => array(
            'Id' => 262144,
            'Label' => 'Miniature',
        ),
        327680 => array(
            'Id' => 327680,
            'Label' => 'Dynamic Tone',
        ),
        393217 => array(
            'Id' => 393217,
            'Label' => 'Partial Color Red',
        ),
        393218 => array(
            'Id' => 393218,
            'Label' => 'Partial Color Yellow',
        ),
        393219 => array(
            'Id' => 393219,
            'Label' => 'Partial Color Green',
        ),
        393220 => array(
            'Id' => 393220,
            'Label' => 'Partial Color Blue',
        ),
        393221 => array(
            'Id' => 393221,
            'Label' => 'Partial Color Orange',
        ),
        393222 => array(
            'Id' => 393222,
            'Label' => 'Partial Color Purple',
        ),
        458752 => array(
            'Id' => 458752,
            'Label' => 'Soft Focus',
        ),
        589824 => array(
            'Id' => 589824,
            'Label' => 'Low Key',
        ),
    );

}
