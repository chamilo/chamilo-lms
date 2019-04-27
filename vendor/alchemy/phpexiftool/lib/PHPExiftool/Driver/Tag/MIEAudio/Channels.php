<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIEAudio;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Channels extends AbstractTag
{

    protected $Id = 'Channels';

    protected $Name = 'Channels';

    protected $FullName = 'MIE::Audio';

    protected $GroupName = 'MIE-Audio';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Audio';

    protected $g2 = 'Audio';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Channels';

}
