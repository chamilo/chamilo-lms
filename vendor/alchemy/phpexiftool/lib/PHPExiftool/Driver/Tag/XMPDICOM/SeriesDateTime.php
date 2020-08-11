<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPDICOM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SeriesDateTime extends AbstractTag
{

    protected $Id = 'SeriesDateTime';

    protected $Name = 'SeriesDateTime';

    protected $FullName = 'XMP::DICOM';

    protected $GroupName = 'XMP-DICOM';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-DICOM';

    protected $g2 = 'Image';

    protected $Type = 'date';

    protected $Writable = true;

    protected $Description = 'Series Date Time';

    protected $local_g2 = 'Time';

}
