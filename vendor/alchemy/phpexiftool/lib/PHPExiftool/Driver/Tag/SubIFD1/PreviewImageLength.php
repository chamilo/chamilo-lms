<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SubIFD1;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PreviewImageLength extends AbstractTag
{

    protected $Id = 279;

    protected $Name = 'PreviewImageLength';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'SubIFD1';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Preview Image Length';

    protected $local_g1 = 'SubIFD1';

    protected $flag_Protected = true;

    protected $Index = 3;

}
