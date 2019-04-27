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
class ImageAlterationConstraints extends AbstractTag
{

    protected $Id = 'ImageAlterationConstraints';

    protected $Name = 'ImageAlterationConstraints';

    protected $FullName = 'XMP::plus';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Image Alteration Constraints';

    protected $flag_List = true;

    protected $flag_Bag = true;

    protected $Values = array(
        'AL-CLR' => array(
            'Id' => 'AL-CLR',
            'Label' => 'No Colorization',
        ),
        'AL-CRP' => array(
            'Id' => 'AL-CRP',
            'Label' => 'No Cropping',
        ),
        'AL-DCL' => array(
            'Id' => 'AL-DCL',
            'Label' => 'No De-Colorization',
        ),
        'AL-FLP' => array(
            'Id' => 'AL-FLP',
            'Label' => 'No Flipping',
        ),
        'AL-MRG' => array(
            'Id' => 'AL-MRG',
            'Label' => 'No Merging',
        ),
        'AL-RET' => array(
            'Id' => 'AL-RET',
            'Label' => 'No Retouching',
        ),
    );

}
