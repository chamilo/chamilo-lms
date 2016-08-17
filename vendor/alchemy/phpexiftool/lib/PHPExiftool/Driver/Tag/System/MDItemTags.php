<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\System;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MDItemTags extends AbstractTag
{

    protected $Id = 'MDItemTags';

    protected $Name = 'MDItemTags';

    protected $FullName = 'Extra';

    protected $GroupName = 'System';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'MD Item Tags';

    protected $local_g1 = 'System';

    protected $local_g2 = 'Other';

}
