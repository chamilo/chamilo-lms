<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPMwgKw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class HierarchicalKeywords4Applied extends AbstractTag
{

    protected $Id = 'KeywordsHierarchyChildrenChildrenChildrenApplied';

    protected $Name = 'HierarchicalKeywords4Applied';

    protected $FullName = 'MWG::Keywords';

    protected $GroupName = 'XMP-mwg-kw';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-mwg-kw';

    protected $g2 = 'Image';

    protected $Type = 'boolean';

    protected $Writable = true;

    protected $Description = 'Hierarchical Keywords 4 Applied';

    protected $flag_List = true;

}
