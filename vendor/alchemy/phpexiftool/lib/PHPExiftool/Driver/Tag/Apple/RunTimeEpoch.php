<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Apple;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RunTimeEpoch extends AbstractTag
{

    protected $Id = 'epoch';

    protected $Name = 'RunTimeEpoch';

    protected $FullName = 'Apple::RunTime';

    protected $GroupName = 'Apple';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Apple';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Run Time Epoch';

    protected $flag_Permanent = true;

}
