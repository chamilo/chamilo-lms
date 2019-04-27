<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPMwgRs;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RegionAreaD extends AbstractTag
{

    protected $Id = 'RegionsRegionListAreaD';

    protected $Name = 'RegionAreaD';

    protected $FullName = 'MWG::Regions';

    protected $GroupName = 'XMP-mwg-rs';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-mwg-rs';

    protected $g2 = 'Image';

    protected $Type = 'real';

    protected $Writable = true;

    protected $Description = 'Region Area D';

    protected $flag_List = true;

}
