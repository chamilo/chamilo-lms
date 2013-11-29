<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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
class DynamicRangeExpansion extends AbstractTag
{

    protected $Id = 105;

    protected $Name = 'DynamicRangeExpansion';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Dynamic Range Expansion';

    protected $flag_Permanent = true;

    protected $Values = array(
        '0 0 0 0' => array(
            'Id' => '0 0 0 0',
            'Label' => 'Off',
        ),
        '1 0 0 0' => array(
            'Id' => '1 0 0 0',
            'Label' => 'On',
        ),
    );

}
