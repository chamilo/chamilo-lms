<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
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
class Orientation2 extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Orientation2';

    protected $FullName = 'Sony::MoreSettings';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Orientation 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Horizontal (normal)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Rotate 180',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Rotate 90 CW',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Rotate 270 CW',
        ),
    );

    protected $Index = 'mixed';

}
