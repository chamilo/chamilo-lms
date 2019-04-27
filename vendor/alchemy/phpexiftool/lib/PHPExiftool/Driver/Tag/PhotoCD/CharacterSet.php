<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PhotoCD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CharacterSet extends AbstractTag
{

    protected $Id = 132;

    protected $Name = 'CharacterSet';

    protected $FullName = 'PhotoCD::Main';

    protected $GroupName = 'PhotoCD';

    protected $g0 = 'PhotoCD';

    protected $g1 = 'PhotoCD';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Character Set';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => '38 characters ISO 646',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '65 characters ISO 646',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '95 characters ISO 646',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '191 characters ISO 8850-1',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'ISO 2022',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Includes characters not ISO 2375 registered',
        ),
    );

}
