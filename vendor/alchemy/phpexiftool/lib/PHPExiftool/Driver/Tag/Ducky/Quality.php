<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Ducky;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Quality extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'Quality';

    protected $FullName = 'APP12::Ducky';

    protected $GroupName = 'Ducky';

    protected $g0 = 'Ducky';

    protected $g1 = 'Ducky';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Quality';

    protected $flag_Avoid = true;

}
