<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SPIFF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageHeight extends AbstractTag
{

    protected $Id = 6;

    protected $Name = 'ImageHeight';

    protected $FullName = 'JPEG::SPIFF';

    protected $GroupName = 'SPIFF';

    protected $g0 = 'APP8';

    protected $g1 = 'SPIFF';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Image Height';

}
