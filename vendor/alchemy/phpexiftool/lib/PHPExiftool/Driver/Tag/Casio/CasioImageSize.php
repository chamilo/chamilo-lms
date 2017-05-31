<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Casio;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CasioImageSize extends AbstractTag
{

    protected $Id = 9;

    protected $Name = 'CasioImageSize';

    protected $FullName = 'Casio::Type2';

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Casio Image Size';

    protected $local_g2 = 'Image';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '640x480',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '1600x1200',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '2048x1536',
        ),
        20 => array(
            'Id' => 20,
            'Label' => '2288x1712',
        ),
        21 => array(
            'Id' => 21,
            'Label' => '2592x1944',
        ),
        22 => array(
            'Id' => 22,
            'Label' => '2304x1728',
        ),
        36 => array(
            'Id' => 36,
            'Label' => '3008x2008',
        ),
    );

}
