<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ExifTool;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class NewGUID extends AbstractTag
{

    protected $Id = 'NewGUID';

    protected $Name = 'NewGUID';

    protected $FullName = 'Extra';

    protected $GroupName = 'ExifTool';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'New GUID';

    protected $local_g0 = 'ExifTool';

    protected $local_g1 = 'ExifTool';

    protected $local_g2 = 'Other';

}
