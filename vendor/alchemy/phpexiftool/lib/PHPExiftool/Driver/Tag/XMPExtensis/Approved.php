<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPExtensis;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Approved extends AbstractTag
{

    protected $Id = 'Approved';

    protected $Name = 'Approved';

    protected $FullName = 'XMP::extensis';

    protected $GroupName = 'XMP-extensis';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-extensis';

    protected $g2 = 'Image';

    protected $Type = 'boolean';

    protected $Writable = true;

    protected $Description = 'Approved';

}
