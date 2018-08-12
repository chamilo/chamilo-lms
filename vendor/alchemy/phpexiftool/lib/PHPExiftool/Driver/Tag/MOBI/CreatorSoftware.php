<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MOBI;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CreatorSoftware extends AbstractTag
{

    protected $Id = 204;

    protected $Name = 'CreatorSoftware';

    protected $FullName = 'Palm::EXTH';

    protected $GroupName = 'MOBI';

    protected $g0 = 'Palm';

    protected $g1 = 'MOBI';

    protected $g2 = 'Document';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Creator Software';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Mobigen',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Mobipocket',
        ),
        200 => array(
            'Id' => 200,
            'Label' => 'Kindlegen (Windows)',
        ),
        201 => array(
            'Id' => 201,
            'Label' => 'Kindlegen (Linux)',
        ),
        202 => array(
            'Id' => 202,
            'Label' => 'Kindlegen (Mac)',
        ),
    );

}
