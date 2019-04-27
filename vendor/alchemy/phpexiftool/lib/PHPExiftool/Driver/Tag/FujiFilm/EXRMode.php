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
class EXRMode extends AbstractTag
{

    protected $Id = 4148;

    protected $Name = 'EXRMode';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'EXR Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        256 => array(
            'Id' => 256,
            'Label' => 'HR (High Resolution)',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'SN (Signal to Noise priority)',
        ),
        768 => array(
            'Id' => 768,
            'Label' => 'DR (Dynamic Range priority)',
        ),
    );

}
