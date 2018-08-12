<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ITC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DataLocation extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'DataLocation';

    protected $FullName = 'ITC::Item';

    protected $GroupName = 'ITC';

    protected $g0 = 'ITC';

    protected $g1 = 'ITC';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Data Location';

    protected $MaxLength = 4;

    protected $Values = array(
        'down' => array(
            'Id' => 'down',
            'Label' => 'Downloaded Separately',
        ),
        'locl' => array(
            'Id' => 'locl',
            'Label' => 'Local Music File',
        ),
    );

}
