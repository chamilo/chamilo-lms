<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPPrism;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class OriginPlatform extends AbstractTag
{

    protected $Id = 'originPlatform';

    protected $Name = 'OriginPlatform';

    protected $FullName = 'XMP::prism';

    protected $GroupName = 'XMP-prism';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-prism';

    protected $g2 = 'Document';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Origin Platform';

    protected $flag_Avoid = true;

    protected $flag_List = true;

    protected $flag_Bag = true;

    protected $Values = array(
        'broadcast' => array(
            'Id' => 'broadcast',
            'Label' => 'Broadcast',
        ),
        'email' => array(
            'Id' => 'email',
            'Label' => 'E-Mail',
        ),
        'mobile' => array(
            'Id' => 'mobile',
            'Label' => 'Mobile',
        ),
        'other' => array(
            'Id' => 'other',
            'Label' => 'Other',
        ),
        'print' => array(
            'Id' => 'print',
            'Label' => 'Print',
        ),
        'recordableMedia' => array(
            'Id' => 'recordableMedia',
            'Label' => 'Recordable Media',
        ),
        'web' => array(
            'Id' => 'web',
            'Label' => 'Web',
        ),
    );

}
