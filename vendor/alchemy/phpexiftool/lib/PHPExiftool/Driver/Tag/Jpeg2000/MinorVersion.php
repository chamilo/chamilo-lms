<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Jpeg2000;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MinorVersion extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'MinorVersion';

    protected $FullName = 'Jpeg2000::FileType';

    protected $GroupName = 'Jpeg2000';

    protected $g0 = 'Jpeg2000';

    protected $g1 = 'Jpeg2000';

    protected $g2 = 'Video';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Minor Version';

    protected $MaxLength = 4;

}
