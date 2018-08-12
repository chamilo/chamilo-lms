<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPSwf;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Type extends AbstractTag
{

    protected $Id = 'type';

    protected $Name = 'Type';

    protected $FullName = 'XMP::swf';

    protected $GroupName = 'XMP-swf';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-swf';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Type';

    protected $flag_Avoid = true;

}
