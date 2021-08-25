<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Samsung;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DeviceType extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'DeviceType';

    protected $FullName = 'Samsung::Type2';

    protected $GroupName = 'Samsung';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Samsung';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Device Type';

    protected $flag_Permanent = true;

    protected $Values = array(
        4096 => array(
            'Id' => 4096,
            'Label' => 'Compact Digital Camera',
        ),
        8192 => array(
            'Id' => 8192,
            'Label' => 'High-end NX Camera',
        ),
        12288 => array(
            'Id' => 12288,
            'Label' => 'HXM Video Camera',
        ),
        73728 => array(
            'Id' => 73728,
            'Label' => 'Cell Phone',
        ),
        3145728 => array(
            'Id' => 3145728,
            'Label' => 'SMX Video Camera',
        ),
    );

}
