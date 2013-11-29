<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SigmaRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LensType extends AbstractTag
{

    protected $Id = 'LENSMODEL';

    protected $Name = 'LensType';

    protected $FullName = 'SigmaRaw::Properties';

    protected $GroupName = 'SigmaRaw';

    protected $g0 = 'SigmaRaw';

    protected $g1 = 'SigmaRaw';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Lens Type';

    protected $Values = array(
        16 => array(
            'Id' => 16,
            'Label' => 'Sigma 18-50mm F3.5-5.6 DC',
        ),
        129 => array(
            'Id' => 129,
            'Label' => 'Sigma 14mm F2.8 EX Aspherical',
        ),
        131 => array(
            'Id' => 131,
            'Label' => 'Sigma 17-70mm F2.8-4.5 DC Macro',
        ),
        145 => array(
            'Id' => 145,
            'Label' => 'Sigma Lens (145)',
        ),
        '145.1' => array(
            'Id' => '145.1',
            'Label' => 'Sigma 15-30mm F3.5-4.5 EX DG Aspherical',
        ),
        '145.2' => array(
            'Id' => '145.2',
            'Label' => 'Sigma 18-50mm F2.8 EX DG',
        ),
        '145.3' => array(
            'Id' => '145.3',
            'Label' => 'Sigma 20-40mm F2.8 EX DG',
        ),
        165 => array(
            'Id' => 165,
            'Label' => 'Sigma 70-200mm F2.8 EX',
        ),
        169 => array(
            'Id' => 169,
            'Label' => 'Sigma 18-50mm F2.8 EX DC',
        ),
        581 => array(
            'Id' => 581,
            'Label' => 'Sigma 18-50mm F2.8 EX DC Macro',
        ),
        8900 => array(
            'Id' => 8900,
            'Label' => 'Sigma 70-300mm F4-5.6 DG OS',
        ),
        'A100' => array(
            'Id' => 'A100',
            'Label' => 'Sigma 24-70mm F2.8 DG Macro',
        ),
    );

}
