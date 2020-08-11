<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Jpeg2000;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MajorBrand extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'MajorBrand';

    protected $FullName = 'Jpeg2000::FileType';

    protected $GroupName = 'Jpeg2000';

    protected $g0 = 'Jpeg2000';

    protected $g1 = 'Jpeg2000';

    protected $g2 = 'Video';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Major Brand';

    protected $MaxLength = 4;

    protected $Values = array(
        'jp2 ' => array(
            'Id' => 'jp2 ',
            'Label' => 'JPEG 2000 Image (.JP2)',
        ),
        'jpm ' => array(
            'Id' => 'jpm ',
            'Label' => 'JPEG 2000 Compound Image (.JPM)',
        ),
        'jpx ' => array(
            'Id' => 'jpx ',
            'Label' => 'JPEG 2000 with extensions (.JPX)',
        ),
    );

}
