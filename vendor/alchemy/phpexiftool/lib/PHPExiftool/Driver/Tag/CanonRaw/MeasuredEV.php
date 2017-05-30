<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MeasuredEV extends AbstractTag
{

    protected $Id = 6164;

    protected $Name = 'MeasuredEV';

    protected $FullName = 'CanonRaw::Main';

    protected $GroupName = 'CanonRaw';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonRaw';

    protected $g2 = 'Camera';

    protected $Type = 'float';

    protected $Writable = true;

    protected $Description = 'Measured EV';

    protected $flag_Permanent = true;

}
