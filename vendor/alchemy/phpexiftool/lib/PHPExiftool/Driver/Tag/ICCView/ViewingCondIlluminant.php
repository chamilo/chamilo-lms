<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ICCView;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ViewingCondIlluminant extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'ViewingCondIlluminant';

    protected $FullName = 'ICC_Profile::ViewingConditions';

    protected $GroupName = 'ICC-view';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC-view';

    protected $g2 = 'Image';

    protected $Type = 'fixed32s';

    protected $Writable = false;

    protected $Description = 'Viewing Cond Illuminant';

    protected $MaxLength = 3;

}
