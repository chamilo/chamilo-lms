<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPPdf;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Trapped extends AbstractTag
{

    protected $Id = 'Trapped';

    protected $Name = 'Trapped';

    protected $FullName = 'XMP::pdf';

    protected $GroupName = 'XMP-pdf';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-pdf';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Trapped';

    protected $Values = array(
        False => array(
            'Id' => False,
            'Label' => False,
        ),
        True => array(
            'Id' => True,
            'Label' => True,
        ),
        'Unknown' => array(
            'Id' => 'Unknown',
            'Label' => 'Unknown',
        ),
    );

}
