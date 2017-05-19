<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XML;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DocSecurity extends AbstractTag
{

    protected $Id = 'DocSecurity';

    protected $Name = 'DocSecurity';

    protected $FullName = 'OOXML::Main';

    protected $GroupName = 'XML';

    protected $g0 = 'XML';

    protected $g1 = 'XML';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Doc Security';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Password protected',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Read-only recommended',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Read-only enforced',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Locked for annotations',
        ),
    );

}
