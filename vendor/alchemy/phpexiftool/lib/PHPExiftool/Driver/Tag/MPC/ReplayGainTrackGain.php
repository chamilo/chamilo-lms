<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MPC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ReplayGainTrackGain extends AbstractTag
{

    protected $Id = 'Bit112-127';

    protected $Name = 'ReplayGainTrackGain';

    protected $FullName = 'MPC::Main';

    protected $GroupName = 'MPC';

    protected $g0 = 'MPC';

    protected $g1 = 'MPC';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Replay Gain Track Gain';

}
