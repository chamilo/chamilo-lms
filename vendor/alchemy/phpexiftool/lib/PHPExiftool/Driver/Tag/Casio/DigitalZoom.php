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
class DigitalZoom extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'DigitalZoom';

    protected $FullName = 'Casio::Main';

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Digital Zoom';

    protected $flag_Permanent = true;

    protected $Values = array(
        65536 => array(
            'Id' => 65536,
            'Label' => 'Off',
        ),
        65537 => array(
            'Id' => 65537,
            'Label' => '2x',
        ),
        104857 => array(
            'Id' => 104857,
            'Label' => '1.6x',
        ),
        131072 => array(
            'Id' => 131072,
            'Label' => '2x',
        ),
        209715 => array(
            'Id' => 209715,
            'Label' => '3.2x',
        ),
        262144 => array(
            'Id' => 262144,
            'Label' => '4x',
        ),
    );

}
