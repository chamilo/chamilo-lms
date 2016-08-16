<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MNG;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CloneType extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'CloneType';

    protected $FullName = 'MNG::CloneObject';

    protected $GroupName = 'MNG';

    protected $g0 = 'MNG';

    protected $g1 = 'MNG';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Clone Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Full',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Parital',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Renumber object',
        ),
    );

}
