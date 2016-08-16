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
class CreditLineRequired extends AbstractTag
{

    protected $Id = 'CreditLineRequired';

    protected $Name = 'CreditLineRequired';

    protected $FullName = 'XMP::plus';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Credit Line Required';

    protected $Values = array(
        'CR-CAI' => array(
            'Id' => 'CR-CAI',
            'Label' => 'Credit Adjacent To Image',
        ),
        'CR-CCA' => array(
            'Id' => 'CR-CCA',
            'Label' => 'Credit in Credits Area',
        ),
        'CR-COI' => array(
            'Id' => 'CR-COI',
            'Label' => 'Credit on Image',
        ),
        'CR-NRQ' => array(
            'Id' => 'CR-NRQ',
            'Label' => 'Not Required',
        ),
    );

}
