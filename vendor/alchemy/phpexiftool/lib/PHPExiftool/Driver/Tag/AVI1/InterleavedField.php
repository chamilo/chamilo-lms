<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\AVI1;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class InterleavedField extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'InterleavedField';

    protected $FullName = 'JPEG::AVI1';

    protected $GroupName = 'AVI1';

    protected $g0 = 'APP0';

    protected $g1 = 'AVI1';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Interleaved Field';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Not Interleaved',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Odd',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Even',
        ),
    );

}
