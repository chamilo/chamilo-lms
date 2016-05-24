<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Flash;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FileAttributes extends AbstractTag
{

    protected $Id = 69;

    protected $Name = 'FileAttributes';

    protected $FullName = 'Flash::Main';

    protected $GroupName = 'Flash';

    protected $g0 = 'Flash';

    protected $g1 = 'Flash';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'File Attributes';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'UseNetwork',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'ActionScript3',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'HasMetadata',
        ),
    );

}
