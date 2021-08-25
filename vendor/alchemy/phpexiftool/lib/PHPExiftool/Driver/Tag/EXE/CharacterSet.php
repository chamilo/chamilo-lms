<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\EXE;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CharacterSet extends AbstractTag
{

    protected $Id = 'CharacterSet';

    protected $Name = 'CharacterSet';

    protected $FullName = 'EXE::PEString';

    protected $GroupName = 'EXE';

    protected $g0 = 'EXE';

    protected $g1 = 'EXE';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Character Set';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'ASCII',
        ),
        '03A4' => array(
            'Id' => '03A4',
            'Label' => 'Windows, Japan (Shift - JIS X-0208)',
        ),
        '03A8' => array(
            'Id' => '03A8',
            'Label' => 'Windows, Chinese (Simplified)',
        ),
        '03B5' => array(
            'Id' => '03B5',
            'Label' => 'Windows, Korea (Shift - KSC 5601)',
        ),
        '03B6' => array(
            'Id' => '03B6',
            'Label' => 'Windows, Taiwan (Big5)',
        ),
        '04B0' => array(
            'Id' => '04B0',
            'Label' => 'Unicode',
        ),
        '04E2' => array(
            'Id' => '04E2',
            'Label' => 'Windows, Latin2 (Eastern European)',
        ),
        '04E3' => array(
            'Id' => '04E3',
            'Label' => 'Windows, Cyrillic',
        ),
        '04E4' => array(
            'Id' => '04E4',
            'Label' => 'Windows, Latin1',
        ),
        '04E5' => array(
            'Id' => '04E5',
            'Label' => 'Windows, Greek',
        ),
        '04E6' => array(
            'Id' => '04E6',
            'Label' => 'Windows, Turkish',
        ),
        '04E7' => array(
            'Id' => '04E7',
            'Label' => 'Windows, Hebrew',
        ),
        '04E8' => array(
            'Id' => '04E8',
            'Label' => 'Windows, Arabic',
        ),
    );

}
