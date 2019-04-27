<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MXF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ShotStartPosition extends AbstractTag
{

    protected $Id = '060e2b34.0101.0105.07020103.01090000';

    protected $Name = 'ShotStartPosition';

    protected $FullName = 'MXF::Main';

    protected $GroupName = 'MXF';

    protected $g0 = 'MXF';

    protected $g1 = 'MXF';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Shot Start Position';

}
