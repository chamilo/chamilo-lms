<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Stim;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RepresentativeDisparityNear extends AbstractTag
{

    protected $Id = 14;

    protected $Name = 'RepresentativeDisparityNear';

    protected $FullName = 'Stim::Main';

    protected $GroupName = 'Stim';

    protected $g0 = 'Stim';

    protected $g1 = 'Stim';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Representative Disparity Near';

}
