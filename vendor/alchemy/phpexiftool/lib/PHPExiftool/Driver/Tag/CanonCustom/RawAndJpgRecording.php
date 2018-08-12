<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RawAndJpgRecording extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'RawAndJpgRecording';

    protected $FullName = 'CanonCustom::Functions10D';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Raw And Jpg Recording';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'RAW+Small/Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'RAW+Small/Fine',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'RAW+Medium/Normal',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'RAW+Medium/Fine',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'RAW+Large/Normal',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'RAW+Large/Fine',
        ),
    );

}
