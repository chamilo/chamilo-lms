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
class ContentSignatureAlgorithm extends AbstractTag
{

    protected $Id = 2021;

    protected $Name = 'ContentSignatureAlgorithm';

    protected $FullName = 'Matroska::Main';

    protected $GroupName = 'Matroska';

    protected $g0 = 'Matroska';

    protected $g1 = 'Matroska';

    protected $g2 = 'Video';

    protected $Type = 'unsigned';

    protected $Writable = false;

    protected $Description = 'Content Signature Algorithm';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Not Signed',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'RSA',
        ),
    );

}
