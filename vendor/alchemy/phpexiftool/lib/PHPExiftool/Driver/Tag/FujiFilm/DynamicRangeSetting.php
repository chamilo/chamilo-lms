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
class DynamicRangeSetting extends AbstractTag
{

    protected $Id = 5122;

    protected $Name = 'DynamicRangeSetting';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Dynamic Range Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto (100-400%)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Standard (100%)',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Wide1 (230%)',
        ),
        513 => array(
            'Id' => 513,
            'Label' => 'Wide2 (400%)',
        ),
        32768 => array(
            'Id' => 32768,
            'Label' => 'Film Simulation',
        ),
    );

}
