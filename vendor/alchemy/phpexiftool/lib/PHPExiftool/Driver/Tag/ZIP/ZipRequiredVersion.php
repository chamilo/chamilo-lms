<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ZIP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ZipRequiredVersion extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'ZipRequiredVersion';

    protected $FullName = 'ZIP::Main';

    protected $GroupName = 'ZIP';

    protected $g0 = 'ZIP';

    protected $g1 = 'ZIP';

    protected $g2 = 'Other';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Zip Required Version';

}
