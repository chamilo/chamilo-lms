<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\EPPIM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PrintIM extends AbstractTag
{

    protected $Id = 50341;

    protected $Name = 'PrintIM';

    protected $FullName = 'JPEG::EPPIM';

    protected $GroupName = 'EPPIM';

    protected $g0 = 'APP6';

    protected $g1 = 'EPPIM';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Print Image Matching';

}
