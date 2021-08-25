<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPXmpBJ;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class JobRefName extends AbstractTag
{

    protected $Id = 'JobRefName';

    protected $Name = 'JobRefName';

    protected $FullName = 'XMP::xmpBJ';

    protected $GroupName = 'XMP-xmpBJ';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpBJ';

    protected $g2 = 'Other';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Job Ref Name';

    protected $flag_List = true;

}
