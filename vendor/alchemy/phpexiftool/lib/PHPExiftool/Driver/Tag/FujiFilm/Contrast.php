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
class Contrast extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Contrast';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Contrast';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 128,
            'Label' => 'Medium High',
        ),
        2 => array(
            'Id' => 256,
            'Label' => 'High',
        ),
        3 => array(
            'Id' => 384,
            'Label' => 'Medium Low',
        ),
        4 => array(
            'Id' => 512,
            'Label' => 'Low',
        ),
        5 => array(
            'Id' => 32768,
            'Label' => 'Film Simulation',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        7 => array(
            'Id' => 256,
            'Label' => 'High',
        ),
        8 => array(
            'Id' => 768,
            'Label' => 'Low',
        ),
    );

}
