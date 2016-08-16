<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Matroska;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ChapterProcessTime extends AbstractTag
{

    protected $Id = 10530;

    protected $Name = 'ChapterProcessTime';

    protected $FullName = 'Matroska::Main';

    protected $GroupName = 'Matroska';

    protected $g0 = 'Matroska';

    protected $g1 = 'Matroska';

    protected $g2 = 'Video';

    protected $Type = 'unsigned';

    protected $Writable = false;

    protected $Description = 'Chapter Process Time';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'For Duration of Chapter',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Before Chapter',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'After Chapter',
        ),
    );

}
