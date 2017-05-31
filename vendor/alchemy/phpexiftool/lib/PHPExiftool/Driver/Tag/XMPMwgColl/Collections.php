<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPMwgColl;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Collections extends AbstractTag
{

    protected $Id = 'Collections';

    protected $Name = 'Collections';

    protected $FullName = 'MWG::Collections';

    protected $GroupName = 'XMP-mwg-coll';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-mwg-coll';

    protected $g2 = 'Image';

    protected $Type = 'struct';

    protected $Writable = true;

    protected $Description = 'Collections';

    protected $flag_List = true;

    protected $flag_Bag = true;

}
