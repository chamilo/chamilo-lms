<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Quality2 extends AbstractTag
{

    protected $Id = 41;

    protected $Name = 'Quality2';

    protected $FullName = 'Sony::Tag9400';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Quality 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'JPEG',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'RAW',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'RAW + JPEG',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'JPEG + MPO',
        ),
    );

}
