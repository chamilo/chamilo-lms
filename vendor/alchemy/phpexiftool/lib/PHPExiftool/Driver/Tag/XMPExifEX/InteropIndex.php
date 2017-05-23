<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPExifEX;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class InteropIndex extends AbstractTag
{

    protected $Id = 'InteroperabilityIndex';

    protected $Name = 'InteropIndex';

    protected $FullName = 'XMP::exifEX';

    protected $GroupName = 'XMP-exifEX';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-exifEX';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Interoperability Index';

    protected $Values = array(
        'R03' => array(
            'Id' => 'R03',
            'Label' => 'R03 - DCF option file (Adobe RGB)',
        ),
        'R98' => array(
            'Id' => 'R98',
            'Label' => 'R98 - DCF basic file (sRGB)',
        ),
        'THM' => array(
            'Id' => 'THM',
            'Label' => 'THM - DCF thumbnail file',
        ),
    );

}
