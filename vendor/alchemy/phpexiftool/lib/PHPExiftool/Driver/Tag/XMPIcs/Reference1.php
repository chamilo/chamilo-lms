<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPIcs;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Reference1 extends AbstractTag
{

    protected $Id = 'TagStructureReference';

    protected $Name = 'Reference1';

    protected $FullName = 'XMP::ics';

    protected $GroupName = 'XMP-ics';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-ics';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Reference 1';

    protected $flag_List = true;

}
