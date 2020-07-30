<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NITF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BlocksPerRow extends AbstractTag
{

    protected $Id = 3;

    protected $Name = 'BlocksPerRow';

    protected $FullName = 'JPEG::NITF';

    protected $GroupName = 'NITF';

    protected $g0 = 'APP6';

    protected $g1 = 'NITF';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Blocks Per Row';

}
