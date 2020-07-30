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
class Face6Category extends AbstractTag
{

    protected $Id = 'Face6Category';

    protected $Name = 'Face6Category';

    protected $FullName = 'FujiFilm::FaceRecInfo';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Face 6 Category';

    protected $flag_Permanent = true;

    protected $Values = array(
        2 => array(
            'Id' => 2,
            'Label' => 'Partner',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Family',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Friend',
        ),
    );

}
