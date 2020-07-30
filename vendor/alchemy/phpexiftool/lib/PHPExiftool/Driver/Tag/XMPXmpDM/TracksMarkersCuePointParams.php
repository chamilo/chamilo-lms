<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPXmpDM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class TracksMarkersCuePointParams extends AbstractTag
{

    protected $Id = 'TracksMarkersCuePointParams';

    protected $Name = 'TracksMarkersCuePointParams';

    protected $FullName = 'XMP::xmpDM';

    protected $GroupName = 'XMP-xmpDM';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpDM';

    protected $g2 = 'Image';

    protected $Type = 'struct';

    protected $Writable = true;

    protected $Description = 'Tracks Markers Cue Point Params';

    protected $flag_List = true;

    protected $flag_Seq = true;

}
