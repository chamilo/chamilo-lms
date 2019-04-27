<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Photoshop;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class XResolution extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'XResolution';

    protected $FullName = 'Photoshop::Resolution';

    protected $GroupName = 'Photoshop';

    protected $g0 = 'Photoshop';

    protected $g1 = 'Photoshop';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'X Resolution';

}
