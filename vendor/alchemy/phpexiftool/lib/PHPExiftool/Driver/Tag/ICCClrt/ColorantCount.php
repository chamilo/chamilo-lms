<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ICCClrt;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorantCount extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'ColorantCount';

    protected $FullName = 'ICC_Profile::ColorantTable';

    protected $GroupName = 'ICC-clrt';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC-clrt';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Colorant Count';

}
