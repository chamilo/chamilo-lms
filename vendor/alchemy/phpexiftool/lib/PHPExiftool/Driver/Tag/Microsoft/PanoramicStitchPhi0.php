<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Microsoft;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PanoramicStitchPhi0 extends AbstractTag
{

    protected $Id = 5;

    protected $Name = 'PanoramicStitchPhi0';

    protected $FullName = 'Microsoft::Stitch';

    protected $GroupName = 'Microsoft';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Microsoft';

    protected $g2 = 'Image';

    protected $Type = 'float';

    protected $Writable = true;

    protected $Description = 'Panoramic Stitch Phi 0';

    protected $flag_Permanent = true;

}
