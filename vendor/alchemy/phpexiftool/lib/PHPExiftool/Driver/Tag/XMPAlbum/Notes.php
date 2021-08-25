<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPAlbum;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Notes extends AbstractTag
{

    protected $Id = 'Notes';

    protected $Name = 'Notes';

    protected $FullName = 'XMP::Album';

    protected $GroupName = 'XMP-album';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-album';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Notes';

}
