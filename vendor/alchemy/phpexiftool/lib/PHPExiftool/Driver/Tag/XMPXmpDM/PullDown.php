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
class PullDown extends AbstractTag
{

    protected $Id = 'pullDown';

    protected $Name = 'PullDown';

    protected $FullName = 'XMP::xmpDM';

    protected $GroupName = 'XMP-xmpDM';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpDM';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Pull Down';

    protected $Values = array(
        'SSWWW' => array(
            'Id' => 'SSWWW',
            'Label' => 'SSWWW',
        ),
        'SWWWS' => array(
            'Id' => 'SWWWS',
            'Label' => 'SWWWS',
        ),
        'SWWWW' => array(
            'Id' => 'SWWWW',
            'Label' => 'SWWWW',
        ),
        'WSSWW' => array(
            'Id' => 'WSSWW',
            'Label' => 'WSSWW',
        ),
        'WSWWW' => array(
            'Id' => 'WSWWW',
            'Label' => 'WSWWW',
        ),
        'WWSSW' => array(
            'Id' => 'WWSSW',
            'Label' => 'WWSSW',
        ),
        'WWSWW' => array(
            'Id' => 'WWSWW',
            'Label' => 'WWSWW',
        ),
        'WWWSS' => array(
            'Id' => 'WWWSS',
            'Label' => 'WWWSS',
        ),
        'WWWSW' => array(
            'Id' => 'WWWSW',
            'Label' => 'WWWSW',
        ),
        'WWWWS' => array(
            'Id' => 'WWWWS',
            'Label' => 'WWWWS',
        ),
    );

}
