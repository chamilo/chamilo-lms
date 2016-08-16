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
class AELock extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AELock';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'AE Lock';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'On',
        ),
        2 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        3 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
    );

}
