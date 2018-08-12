<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MediaJukebox;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ToolVersion extends AbstractTag
{

    protected $Id = 'Tool_Version';

    protected $Name = 'Tool_Version';

    protected $FullName = 'JPEG::MediaJukebox';

    protected $GroupName = 'MediaJukebox';

    protected $g0 = 'XML';

    protected $g1 = 'MediaJukebox';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Tool Version';

}
