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
class ISOAutoParameters extends AbstractTag
{

    protected $Id = 122;

    protected $Name = 'ISOAutoParameters';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'ISO Auto Parameters';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        '1 0' => array(
            'Id' => '1 0',
            'Label' => 'Slow',
        ),
        '2 0' => array(
            'Id' => '2 0',
            'Label' => 'Standard',
        ),
        '3 0' => array(
            'Id' => '3 0',
            'Label' => 'Fast',
        ),
    );

}
