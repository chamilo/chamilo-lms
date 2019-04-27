<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPPlus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AdultContentWarning extends AbstractTag
{

    protected $Id = 'AdultContentWarning';

    protected $Name = 'AdultContentWarning';

    protected $FullName = 'XMP::plus';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Adult Content Warning';

    protected $Values = array(
        'CW-AWR' => array(
            'Id' => 'CW-AWR',
            'Label' => 'Adult Content Warning Required',
        ),
        'CW-NRQ' => array(
            'Id' => 'CW-NRQ',
            'Label' => 'Not Required',
        ),
        'CW-UNK' => array(
            'Id' => 'CW-UNK',
            'Label' => 'Unknown',
        ),
    );

}
