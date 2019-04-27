<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPPrism;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Channel extends AbstractTag
{

    protected $Id = 'channel';

    protected $Name = 'Channel';

    protected $FullName = 'XMP::prism';

    protected $GroupName = 'XMP-prism';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-prism';

    protected $g2 = 'Document';

    protected $Type = 'struct';

    protected $Writable = true;

    protected $Description = 'Channel';

    protected $flag_Avoid = true;

    protected $flag_List = true;

    protected $flag_Bag = true;

}
