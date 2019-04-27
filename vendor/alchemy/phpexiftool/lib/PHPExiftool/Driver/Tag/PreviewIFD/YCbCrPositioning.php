<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PreviewIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class YCbCrPositioning extends AbstractTag
{

    protected $Id = 531;

    protected $Name = 'YCbCrPositioning';

    protected $FullName = 'Nikon::PreviewIFD';

    protected $GroupName = 'PreviewIFD';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'PreviewIFD';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Y Cb Cr Positioning';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Centered',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Co-sited',
        ),
    );

}
