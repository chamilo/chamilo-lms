<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XML;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DateTimeOriginal extends AbstractTag
{

    protected $Id = 'MetaDataList//DateTimeOriginal';

    protected $Name = 'DateTimeOriginal';

    protected $FullName = 'PLIST::Main';

    protected $GroupName = 'XML';

    protected $g0 = 'PLIST';

    protected $g1 = 'XML';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Date/Time Original';

    protected $local_g2 = 'Time';

}
