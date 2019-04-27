<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\HP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CameraDateTime extends AbstractTag
{

    protected $Id = 20;

    protected $Name = 'CameraDateTime';

    protected $FullName = 'mixed';

    protected $GroupName = 'HP';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'HP';

    protected $g2 = 'Camera';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Camera Date Time';

    protected $local_g2 = 'Time';

    protected $flag_Permanent = true;

    protected $MaxLength = 20;

}
