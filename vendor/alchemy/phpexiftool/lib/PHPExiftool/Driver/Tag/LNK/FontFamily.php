<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\LNK;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FontFamily extends AbstractTag
{

    protected $Id = 36;

    protected $Name = 'FontFamily';

    protected $FullName = 'LNK::ConsoleData';

    protected $GroupName = 'LNK';

    protected $g0 = 'LNK';

    protected $g1 = 'LNK';

    protected $g2 = 'Other';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Font Family';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Don\'t Care',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Roman',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Swiss',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Modern',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Script',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'Decorative',
        ),
    );

}
