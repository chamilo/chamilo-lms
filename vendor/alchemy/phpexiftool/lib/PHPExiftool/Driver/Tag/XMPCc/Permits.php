<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPCc;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Permits extends AbstractTag
{

    protected $Id = 'permits';

    protected $Name = 'Permits';

    protected $FullName = 'XMP::cc';

    protected $GroupName = 'XMP-cc';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-cc';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Permits';

    protected $flag_List = true;

    protected $flag_Bag = true;

    protected $Values = array(
        'cc:DerivativeWorks' => array(
            'Id' => 'cc:DerivativeWorks',
            'Label' => 'Derivative Works',
        ),
        'cc:Distribution' => array(
            'Id' => 'cc:Distribution',
            'Label' => 'Distribution',
        ),
        'cc:Reproduction' => array(
            'Id' => 'cc:Reproduction',
            'Label' => 'Reproduction',
        ),
        'cc:Sharing' => array(
            'Id' => 'cc:Sharing',
            'Label' => 'Sharing',
        ),
    );

}
