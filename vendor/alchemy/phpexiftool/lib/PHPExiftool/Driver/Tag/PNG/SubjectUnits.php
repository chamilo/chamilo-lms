<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PNG;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SubjectUnits extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'SubjectUnits';

    protected $FullName = 'PNG::SubjectScale';

    protected $GroupName = 'PNG';

    protected $g0 = 'PNG';

    protected $g1 = 'PNG';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Subject Units';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'meters',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'radians',
        ),
    );

}
