<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\File;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DataSign extends AbstractTag
{

    protected $Id = 780;

    protected $Name = 'DataSign';

    protected $FullName = 'DPX::Main';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Data Sign';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unsigned',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Signed',
        ),
    );

}
