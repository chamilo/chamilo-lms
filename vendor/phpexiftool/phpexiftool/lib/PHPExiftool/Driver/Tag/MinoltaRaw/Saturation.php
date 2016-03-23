<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MinoltaRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Saturation extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'Saturation';

    protected $FullName = 'MinoltaRaw::RIF';

    protected $GroupName = 'MinoltaRaw';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'MinoltaRaw';

    protected $g2 = 'Image';

    protected $Type = 'int8s';

    protected $Writable = true;

    protected $Description = 'Saturation';

    protected $flag_Permanent = true;

}
