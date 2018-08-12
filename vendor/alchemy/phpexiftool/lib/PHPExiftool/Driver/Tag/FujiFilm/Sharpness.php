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
class Sharpness extends AbstractTag
{

    protected $Id = 4097;

    protected $Name = 'Sharpness';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Sharpness';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Soft',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Soft2',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Normal',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Hard',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Hard2',
        ),
        130 => array(
            'Id' => 130,
            'Label' => 'Medium Soft',
        ),
        132 => array(
            'Id' => 132,
            'Label' => 'Medium Hard',
        ),
        32768 => array(
            'Id' => 32768,
            'Label' => 'Film Simulation',
        ),
        65535 => array(
            'Id' => 65535,
            'Label' => 'n/a',
        ),
    );

}
