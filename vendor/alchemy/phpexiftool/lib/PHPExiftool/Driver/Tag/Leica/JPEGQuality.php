<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Leica;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class JPEGQuality extends AbstractTag
{

    protected $Id = 12340;

    protected $Name = 'JPEGQuality';

    protected $FullName = 'Panasonic::Subdir';

    protected $GroupName = 'Leica';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Leica';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'JPEG Quality';

    protected $flag_Permanent = true;

    protected $Values = array(
        94 => array(
            'Id' => 94,
            'Label' => 'Basic',
        ),
        97 => array(
            'Id' => 97,
            'Label' => 'Fine',
        ),
    );

}
