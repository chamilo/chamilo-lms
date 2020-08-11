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
class Studio extends AbstractTag
{

    protected $Id = 'studio//name';

    protected $Name = 'Studio';

    protected $FullName = 'PLIST::Main';

    protected $GroupName = 'XML';

    protected $g0 = 'PLIST';

    protected $g1 = 'XML';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Studio';

    protected $flag_List = true;

}
