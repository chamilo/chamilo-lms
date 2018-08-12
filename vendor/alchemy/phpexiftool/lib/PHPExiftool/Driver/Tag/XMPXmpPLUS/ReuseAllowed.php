<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPXmpPLUS;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ReuseAllowed extends AbstractTag
{

    protected $Id = 'ReuseAllowed';

    protected $Name = 'ReuseAllowed';

    protected $FullName = 'XMP::xmpPLUS';

    protected $GroupName = 'XMP-xmpPLUS';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpPLUS';

    protected $g2 = 'Author';

    protected $Type = 'boolean';

    protected $Writable = true;

    protected $Description = 'Reuse Allowed';

}
