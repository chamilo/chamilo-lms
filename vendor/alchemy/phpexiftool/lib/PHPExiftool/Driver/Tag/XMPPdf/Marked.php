<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPPdf;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Marked extends AbstractTag
{

    protected $Id = 'Marked';

    protected $Name = 'Marked';

    protected $FullName = 'XMP::pdf';

    protected $GroupName = 'XMP-pdf';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-pdf';

    protected $g2 = 'Image';

    protected $Type = 'boolean';

    protected $Writable = true;

    protected $Description = 'Marked';

    protected $flag_Avoid = true;

}
