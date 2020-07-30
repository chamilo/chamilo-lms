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
class LastUpdate extends AbstractTag
{

    protected $Id = 'lastUpdate';

    protected $Name = 'LastUpdate';

    protected $FullName = 'XMP::XML';

    protected $GroupName = 'XML';

    protected $g0 = 'XML';

    protected $g1 = 'XML';

    protected $g2 = 'Unknown';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Last Update';

    protected $local_g2 = 'Time';

}
