<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PICT;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class InvertRgn extends AbstractTag
{

    protected $Id = 131;

    protected $Name = 'InvertRgn';

    protected $FullName = 'PICT::Main';

    protected $GroupName = 'PICT';

    protected $g0 = 'PICT';

    protected $g1 = 'PICT';

    protected $g2 = 'Other';

    protected $Type = 'Rgn';

    protected $Writable = false;

    protected $Description = 'Invert Rgn';

}
