<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPXmpNote;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class HasExtendedXMP extends AbstractTag
{

    protected $Id = 'HasExtendedXMP';

    protected $Name = 'HasExtendedXMP';

    protected $FullName = 'XMP::xmpNote';

    protected $GroupName = 'XMP-xmpNote';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpNote';

    protected $g2 = 'Other';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Has Extended XMP';

    protected $flag_Protected = true;

}
