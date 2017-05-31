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
class ContentEncryptionAlgorithm extends AbstractTag
{

    protected $Id = 2017;

    protected $Name = 'ContentEncryptionAlgorithm';

    protected $FullName = 'Matroska::Main';

    protected $GroupName = 'Matroska';

    protected $g0 = 'Matroska';

    protected $g1 = 'Matroska';

    protected $g2 = 'Video';

    protected $Type = 'unsigned';

    protected $Writable = false;

    protected $Description = 'Content Encryption Algorithm';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Not Encrypted',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'DES',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '3DES',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Twofish',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Blowfish',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'AES',
        ),
    );

}
