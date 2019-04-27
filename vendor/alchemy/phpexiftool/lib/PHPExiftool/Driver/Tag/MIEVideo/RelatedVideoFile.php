<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIEVideo;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RelatedVideoFile extends AbstractTag
{

    protected $Id = 'data';

    protected $Name = 'RelatedVideoFile';

    protected $FullName = 'MIE::Video';

    protected $GroupName = 'MIE-Video';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Video';

    protected $g2 = 'Video';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Related Video File';

    protected $flag_Binary = true;

}
