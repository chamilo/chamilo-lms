<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RealRA3;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class TitleLen extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'TitleLen';

    protected $FullName = 'Real::AudioV3';

    protected $GroupName = 'Real-RA3';

    protected $g0 = 'Real';

    protected $g1 = 'Real-RA3';

    protected $g2 = 'Audio';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Title Len';

}
