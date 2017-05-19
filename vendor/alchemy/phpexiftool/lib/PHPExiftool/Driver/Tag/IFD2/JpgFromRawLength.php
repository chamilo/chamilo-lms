<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IFD2;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class JpgFromRawLength extends AbstractTag
{

    protected $Id = 514;

    protected $Name = 'JpgFromRawLength';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'IFD2';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Jpg From Raw Length';

    protected $local_g1 = 'IFD2';

    protected $flag_Protected = true;

    protected $Index = 6;

}
