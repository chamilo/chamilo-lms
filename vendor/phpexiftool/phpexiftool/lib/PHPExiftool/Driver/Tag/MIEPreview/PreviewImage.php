<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIEPreview;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PreviewImage extends AbstractTag
{

    protected $Id = 'data';

    protected $Name = 'PreviewImage';

    protected $FullName = 'MIE::Preview';

    protected $GroupName = 'MIE-Preview';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Preview';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Preview Image';

    protected $flag_Binary = true;

}
