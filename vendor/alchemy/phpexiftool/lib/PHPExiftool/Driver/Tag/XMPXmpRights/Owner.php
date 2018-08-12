<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPXmpRights;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Owner extends AbstractTag
{

    protected $Id = 'Owner';

    protected $Name = 'Owner';

    protected $FullName = 'XMP::xmpRights';

    protected $GroupName = 'XMP-xmpRights';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpRights';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Owner';

    protected $flag_List = true;

    protected $flag_Bag = true;

}
