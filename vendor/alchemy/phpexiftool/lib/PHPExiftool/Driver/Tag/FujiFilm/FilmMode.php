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
class FilmMode extends AbstractTag
{

    protected $Id = 5121;

    protected $Name = 'FilmMode';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Film Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'F0/Standard (Provia)',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'F1/Studio Portrait',
        ),
        272 => array(
            'Id' => 272,
            'Label' => 'F1a/Studio Portrait Enhanced Saturation',
        ),
        288 => array(
            'Id' => 288,
            'Label' => 'F1b/Studio Portrait Smooth Skin Tone (Astia)',
        ),
        304 => array(
            'Id' => 304,
            'Label' => 'F1c/Studio Portrait Increased Sharpness',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'F2/Fujichrome (Velvia)',
        ),
        768 => array(
            'Id' => 768,
            'Label' => 'F3/Studio Portrait Ex',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'F4/Velvia',
        ),
        1280 => array(
            'Id' => 1280,
            'Label' => 'Pro Neg. Std',
        ),
        1281 => array(
            'Id' => 1281,
            'Label' => 'Pro Neg. Hi',
        ),
        1536 => array(
            'Id' => 1536,
            'Label' => 'Classic Chrome',
        ),
    );

}
