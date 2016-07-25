<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sigma;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LensType extends AbstractTag
{

    protected $Id = 39;

    protected $Name = 'LensType';

    protected $FullName = 'Sigma::Main';

    protected $GroupName = 'Sigma';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sigma';

    protected $g2 = 'Camera';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Lens Type';

    protected $flag_Permanent = true;

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
        583 => array(
            'Id' => 583,
            'Label' => 'Sigma 17-50mm F2.8 EX DC OS',
        ),
        1003 => array(
            'Id' => 1003,
            'Label' => 'Sigma 19mm F2.8',
        ),
        1004 => array(
            'Id' => 1004,
            'Label' => 'Sigma 30mm F2.8',
        ),
        1005 => array(
            'Id' => 1005,
            'Label' => 'Sigma 50mm F2.8 Macro',
        ),
        1007 => array(
            'Id' => 1007,
            'Label' => 'Sigma 30mm F2.8',
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
