<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class YResolution extends AbstractTag
{

    protected $Id = 17;

    protected $Name = 'YResolution';

    protected $FullName = 'Nikon::AVITags';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'rational64u';

    protected $Writable = false;

    protected $Description = 'Y Resolution';

    protected $local_g2 = 'Image';

    protected $flag_Permanent = true;

}
