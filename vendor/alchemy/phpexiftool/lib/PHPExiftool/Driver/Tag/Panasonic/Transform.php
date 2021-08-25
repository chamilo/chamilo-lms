<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Transform extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Transform';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Transform';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        '-1 1' => array(
            'Id' => '-1 1',
            'Label' => 'Slim Low',
        ),
        '-3 2' => array(
            'Id' => '-3 2',
            'Label' => 'Slim High',
        ),
        '0 0' => array(
            'Id' => '0 0',
            'Label' => 'Off',
        ),
        '1 1' => array(
            'Id' => '1 1',
            'Label' => 'Stretch Low',
        ),
        '3 2' => array(
            'Id' => '3 2',
            'Label' => 'Stretch High',
        ),
    );

}
