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
class ISO extends AbstractTag
{

    protected $Id = 52;

    protected $Name = 'ISO';

    protected $FullName = 'mixed';

    protected $GroupName = 'HP';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'HP';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'ISO';

    protected $flag_Permanent = true;

}
