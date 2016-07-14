<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\EXE;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PEType extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'PEType';

    protected $FullName = 'EXE::Main';

    protected $GroupName = 'EXE';

    protected $g0 = 'EXE';

    protected $g1 = 'EXE';

    protected $g2 = 'Other';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'PE Type';

    protected $Values = array(
        267 => array(
            'Id' => 267,
            'Label' => 'PE32',
        ),
        523 => array(
            'Id' => 523,
            'Label' => 'PE32+',
        ),
    );

}
