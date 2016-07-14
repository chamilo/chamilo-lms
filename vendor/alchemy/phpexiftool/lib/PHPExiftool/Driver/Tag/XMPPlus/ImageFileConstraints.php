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
class ImageFileConstraints extends AbstractTag
{

    protected $Id = 'ImageFileConstraints';

    protected $Name = 'ImageFileConstraints';

    protected $FullName = 'XMP::plus';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Image File Constraints';

    protected $flag_List = true;

    protected $flag_Bag = true;

    protected $Values = array(
        'IF-MFN' => array(
            'Id' => 'IF-MFN',
            'Label' => 'Maintain File Name',
        ),
        'IF-MFT' => array(
            'Id' => 'IF-MFT',
            'Label' => 'Maintain File Type',
        ),
        'IF-MID' => array(
            'Id' => 'IF-MID',
            'Label' => 'Maintain ID in File Name',
        ),
        'IF-MMD' => array(
            'Id' => 'IF-MMD',
            'Label' => 'Maintain Metadata',
        ),
    );

}
