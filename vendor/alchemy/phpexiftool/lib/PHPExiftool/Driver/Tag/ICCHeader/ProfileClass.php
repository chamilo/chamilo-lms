<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ICCHeader;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ProfileClass extends AbstractTag
{

    protected $Id = 12;

    protected $Name = 'ProfileClass';

    protected $FullName = 'ICC_Profile::Header';

    protected $GroupName = 'ICC-header';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC-header';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Profile Class';

    protected $MaxLength = 4;

    protected $Values = array(
        'abst' => array(
            'Id' => 'abst',
            'Label' => 'Abstract Profile',
        ),
        'link' => array(
            'Id' => 'link',
            'Label' => 'DeviceLink Profile',
        ),
        'mntr' => array(
            'Id' => 'mntr',
            'Label' => 'Display Device Profile',
        ),
        'nkpf' => array(
            'Id' => 'nkpf',
            'Label' => 'Nikon Input Device Profile (NON-STANDARD!)',
        ),
        'nmcl' => array(
            'Id' => 'nmcl',
            'Label' => 'NamedColor Profile',
        ),
        'prtr' => array(
            'Id' => 'prtr',
            'Label' => 'Output Device Profile',
        ),
        'scnr' => array(
            'Id' => 'scnr',
            'Label' => 'Input Device Profile',
        ),
        'spac' => array(
            'Id' => 'spac',
            'Label' => 'ColorSpace Conversion Profile',
        ),
    );

}
