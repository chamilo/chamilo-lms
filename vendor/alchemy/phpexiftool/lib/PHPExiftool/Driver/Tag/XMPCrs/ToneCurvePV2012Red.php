<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPCrs;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ToneCurvePV2012Red extends AbstractTag
{

    protected $Id = 'ToneCurvePV2012Red';

    protected $Name = 'ToneCurvePV2012Red';

    protected $FullName = 'XMP::crs';

    protected $GroupName = 'XMP-crs';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-crs';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Tone Curve PV2012 Red';

    protected $flag_List = true;

    protected $flag_Seq = true;

}
