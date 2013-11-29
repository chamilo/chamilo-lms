<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonScan;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DigitalGEM extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'DigitalGEM';

    protected $FullName = 'Nikon::GEM';

    protected $GroupName = 'NikonScan';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonScan';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Digital GEM';

    protected $flag_Permanent = true;

}
