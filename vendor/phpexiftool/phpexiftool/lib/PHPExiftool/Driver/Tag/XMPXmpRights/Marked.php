<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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
class Marked extends AbstractTag
{

    protected $Id = 'Marked';

    protected $Name = 'Marked';

    protected $FullName = 'XMP::xmpRights';

    protected $GroupName = 'XMP-xmpRights';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpRights';

    protected $g2 = 'Author';

    protected $Type = 'boolean';

    protected $Writable = true;

    protected $Description = 'Marked';

}
