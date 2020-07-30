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
class LensMaxApertureRange extends AbstractTag
{

    protected $Id = 43;

    protected $Name = 'LensMaxApertureRange';

    protected $FullName = 'Sigma::Main';

    protected $GroupName = 'Sigma';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sigma';

    protected $g2 = 'Camera';

    protected $Type = 'rational64u';

    protected $Writable = true;

    protected $Description = 'Lens Max Aperture Range';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

}
