<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MOI;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AudioCodec extends AbstractTag
{

    protected $Id = 132;

    protected $Name = 'AudioCodec';

    protected $FullName = 'MOI::Main';

    protected $GroupName = 'MOI';

    protected $g0 = 'MOI';

    protected $g1 = 'MOI';

    protected $g2 = 'Video';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Audio Codec';

    protected $local_g2 = 'Audio';

    protected $Values = array(
        193 => array(
            'Id' => 193,
            'Label' => 'AC3',
        ),
        16385 => array(
            'Id' => 16385,
            'Label' => 'MPEG',
        ),
    );

}
