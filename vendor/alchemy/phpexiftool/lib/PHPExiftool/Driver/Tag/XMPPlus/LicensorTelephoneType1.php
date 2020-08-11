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
class LicensorTelephoneType1 extends AbstractTag
{

    protected $Id = 'LicensorLicensorTelephoneType1';

    protected $Name = 'LicensorTelephoneType1';

    protected $FullName = 'XMP::plus';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Licensor Telephone Type 1';

    protected $flag_List = true;

    protected $Values = array(
        'cell' => array(
            'Id' => 'cell',
            'Label' => 'Cell',
        ),
        'fax' => array(
            'Id' => 'fax',
            'Label' => 'FAX',
        ),
        'home' => array(
            'Id' => 'home',
            'Label' => 'Home',
        ),
        'pager' => array(
            'Id' => 'pager',
            'Label' => 'Pager',
        ),
        'work' => array(
            'Id' => 'work',
            'Label' => 'Work',
        ),
    );

}
