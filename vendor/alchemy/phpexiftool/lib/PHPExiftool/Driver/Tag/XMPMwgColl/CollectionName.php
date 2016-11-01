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
class CollectionName extends AbstractTag
{

    protected $Id = 'CollectionsCollectionName';

    protected $Name = 'CollectionName';

    protected $FullName = 'MWG::Collections';

    protected $GroupName = 'XMP-mwg-coll';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-mwg-coll';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Collection Name';

    protected $flag_List = true;

}
