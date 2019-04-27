<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPPlus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Reuse extends AbstractTag
{

    protected $Id = 'Reuse';

    protected $Name = 'Reuse';

    protected $FullName = 'XMP::plus';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Reuse';

    protected $Values = array(
        'RE-NAP' => array(
            'Id' => 'RE-NAP',
            'Label' => 'Not Applicable',
        ),
        'RE-REU' => array(
            'Id' => 'RE-REU',
            'Label' => 'Repeat Use',
        ),
    );

}
