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
class DataType extends AbstractTag
{

    protected $Id = 16;

    protected $Name = 'DataType';

    protected $FullName = 'ITC::Header';

    protected $GroupName = 'ITC';

    protected $g0 = 'ITC';

    protected $g1 = 'ITC';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Data Type';

    protected $MaxLength = 4;

    protected $Values = array(
        'artw' => array(
            'Id' => 'artw',
            'Label' => 'Artwork',
        ),
    );

}
