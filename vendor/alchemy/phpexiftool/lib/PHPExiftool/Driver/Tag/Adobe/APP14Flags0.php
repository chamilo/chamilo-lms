<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Adobe;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class APP14Flags0 extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'APP14Flags0';

    protected $FullName = 'JPEG::Adobe';

    protected $GroupName = 'Adobe';

    protected $g0 = 'APP14';

    protected $g1 = 'Adobe';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'APP14 Flags 0';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        32768 => array(
            'Id' => 32768,
            'Label' => 'Encoded with Blend=1 downsampling',
        ),
    );

}
