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
class ColorSpecPrecedence extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'ColorSpecPrecedence';

    protected $FullName = 'Jpeg2000::ColorSpec';

    protected $GroupName = 'Jpeg2000';

    protected $g0 = 'Jpeg2000';

    protected $g1 = 'Jpeg2000';

    protected $g2 = 'Image';

    protected $Type = 'int8s';

    protected $Writable = false;

    protected $Description = 'Color Spec Precedence';

}
