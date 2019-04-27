<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sigma;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Saturation extends AbstractTag
{

    protected $Id = 16;

    protected $Name = 'Saturation';

    protected $FullName = 'Sigma::Main';

    protected $GroupName = 'Sigma';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sigma';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Saturation';

    protected $flag_Permanent = true;

    protected $Index = 'mixed';

}
