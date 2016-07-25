<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPDigiKam;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CaptionsDateTimeStamps extends AbstractTag
{

    protected $Id = 'CaptionsDateTimeStamps';

    protected $Name = 'CaptionsDateTimeStamps';

    protected $FullName = 'XMP::digiKam';

    protected $GroupName = 'XMP-digiKam';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-digiKam';

    protected $g2 = 'Image';

    protected $Type = 'lang-alt';

    protected $Writable = true;

    protected $Description = 'Captions Date Time Stamps';

    protected $local_g2 = 'Time';

}
