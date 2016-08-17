<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\GIMP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class XCFVersion extends AbstractTag
{

    protected $Id = 9;

    protected $Name = 'XCFVersion';

    protected $FullName = 'GIMP::Header';

    protected $GroupName = 'GIMP';

    protected $g0 = 'GIMP';

    protected $g1 = 'GIMP';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'XCF Version';

    protected $MaxLength = 5;

    protected $Values = array(
        'file' => array(
            'Id' => 'file',
            'Label' => 0,
        ),
        'v001' => array(
            'Id' => 'v001',
            'Label' => 1,
        ),
        'v002' => array(
            'Id' => 'v002',
            'Label' => 2,
        ),
    );

}
