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
class ShadowTone extends AbstractTag
{

    protected $Id = 4160;

    protected $Name = 'ShadowTone';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'Shadow Tone';

    protected $flag_Permanent = true;

    protected $Values = array(
        '-32' => array(
            'Id' => '-32',
            'Label' => 'Hard',
        ),
        '-16' => array(
            'Id' => '-16',
            'Label' => 'Medium-hard',
        ),
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Medium-soft',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Soft',
        ),
    );

}
