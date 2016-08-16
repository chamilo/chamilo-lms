<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AEFlags extends AbstractTag
{

    protected $Id = 7;

    protected $Name = 'AEFlags';

    protected $FullName = 'Pentax::AEInfo';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AE Flags';

    protected $flag_Permanent = true;

    protected $Values = array(
        8 => array(
            'Id' => 8,
            'Label' => 'AE lock',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Flash recommended?',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Aperture wide open',
        ),
    );

}
