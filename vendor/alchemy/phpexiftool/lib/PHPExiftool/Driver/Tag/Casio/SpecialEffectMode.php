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
class SpecialEffectMode extends AbstractTag
{

    protected $Id = 8310;

    protected $Name = 'SpecialEffectMode';

    protected $FullName = 'Casio::Type2';

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Special Effect Mode';

    protected $flag_Permanent = true;

    protected $MaxLength = 3;

    protected $Values = array(
        '0 0 0' => array(
            'Id' => '0 0 0',
            'Label' => 'Off',
        ),
        '1 0 0' => array(
            'Id' => '1 0 0',
            'Label' => 'Makeup',
        ),
        '2 0 0' => array(
            'Id' => '2 0 0',
            'Label' => 'Mist Removal',
        ),
        '3 0 0' => array(
            'Id' => '3 0 0',
            'Label' => 'Vivid Landscape',
        ),
    );

}
