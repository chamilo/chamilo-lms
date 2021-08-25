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
class EVSteps extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'EVSteps';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'EV Steps';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '1/2 EV Steps',
        ),
        1 => array(
            'Id' => 32,
            'Label' => '1/3 EV Steps',
        ),
        2 => array(
            'Id' => 0,
            'Label' => '1/2 EV Steps',
        ),
        3 => array(
            'Id' => 1,
            'Label' => '1/3 EV Steps',
        ),
    );

}
