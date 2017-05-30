<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RealMDPR;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class StreamMimeLen extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'StreamMimeLen';

    protected $FullName = 'Real::MediaProps';

    protected $GroupName = 'Real-MDPR';

    protected $g0 = 'Real';

    protected $g1 = 'Real-MDPR';

    protected $g2 = 'Video';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Stream Mime Len';

}
