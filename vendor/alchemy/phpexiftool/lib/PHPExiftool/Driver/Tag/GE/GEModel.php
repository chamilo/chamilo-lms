<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\GE;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GEModel extends AbstractTag
{

    protected $Id = 519;

    protected $Name = 'GEModel';

    protected $FullName = 'GE::Main';

    protected $GroupName = 'GE';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'GE';

    protected $g2 = 'Camera';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'GE Model';

    protected $flag_Permanent = true;

}
