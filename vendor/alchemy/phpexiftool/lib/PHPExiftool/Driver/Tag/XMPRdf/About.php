<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPRdf;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class About extends AbstractTag
{

    protected $Id = 'about';

    protected $Name = 'About';

    protected $FullName = 'XMP::rdf';

    protected $GroupName = 'XMP-rdf';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-rdf';

    protected $g2 = 'Document';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'About';

    protected $flag_Unsafe = true;

}
