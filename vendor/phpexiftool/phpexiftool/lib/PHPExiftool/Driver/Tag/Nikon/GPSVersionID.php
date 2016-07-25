<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GPSVersionID extends AbstractTag
{

    protected $Id = 18874368;

    protected $Name = 'GPSVersionID';

    protected $FullName = 'Nikon::NCTG';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'GPS Version ID';

    protected $local_g2 = 'Location';

    protected $flag_Permanent = true;

}
