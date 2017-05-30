<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCapture;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PhotoEffectsRed extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'PhotoEffectsRed';

    protected $FullName = 'NikonCapture::PhotoEffects';

    protected $GroupName = 'NikonCapture';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCapture';

    protected $g2 = 'Image';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Photo Effects Red';

    protected $flag_Permanent = true;

}
