<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class NEFCompression extends AbstractTag
{

    protected $Id = 147;

    protected $Name = 'NEFCompression';

    protected $FullName = 'Nikon::Main';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'NEF Compression';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Lossy (type 1)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Uncompressed',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Lossless',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Lossy (type 2)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Uncompressed (reduced to 12 bit)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Small',
        ),
    );

}
