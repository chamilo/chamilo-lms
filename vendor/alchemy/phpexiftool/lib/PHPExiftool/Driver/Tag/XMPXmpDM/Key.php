<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPXmpDM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Key extends AbstractTag
{

    protected $Id = 'key';

    protected $Name = 'Key';

    protected $FullName = 'XMP::xmpDM';

    protected $GroupName = 'XMP-xmpDM';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpDM';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Key';

    protected $Values = array(
        'A' => array(
            'Id' => 'A',
            'Label' => 'A',
        ),
        'A#' => array(
            'Id' => 'A#',
            'Label' => 'A#',
        ),
        'B' => array(
            'Id' => 'B',
            'Label' => 'B',
        ),
        'C' => array(
            'Id' => 'C',
            'Label' => 'C',
        ),
        'C#' => array(
            'Id' => 'C#',
            'Label' => 'C#',
        ),
        'D' => array(
            'Id' => 'D',
            'Label' => 'D',
        ),
        'D#' => array(
            'Id' => 'D#',
            'Label' => 'D#',
        ),
        'E' => array(
            'Id' => 'E',
            'Label' => 'E',
        ),
        'F' => array(
            'Id' => 'F',
            'Label' => 'F',
        ),
        'F#' => array(
            'Id' => 'F#',
            'Label' => 'F#',
        ),
        'G' => array(
            'Id' => 'G',
            'Label' => 'G',
        ),
        'G#' => array(
            'Id' => 'G#',
            'Label' => 'G#',
        ),
    );

}
