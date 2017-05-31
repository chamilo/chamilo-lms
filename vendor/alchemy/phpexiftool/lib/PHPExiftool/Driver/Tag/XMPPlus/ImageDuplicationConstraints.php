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
class ImageDuplicationConstraints extends AbstractTag
{

    protected $Id = 'ImageDuplicationConstraints';

    protected $Name = 'ImageDuplicationConstraints';

    protected $FullName = 'XMP::plus';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Image Duplication Constraints';

    protected $Values = array(
        'DP-LIC' => array(
            'Id' => 'DP-LIC',
            'Label' => 'Duplication Only as Necessary Under License',
        ),
        'DP-NDC' => array(
            'Id' => 'DP-NDC',
            'Label' => 'No Duplication Constraints',
        ),
        'DP-NOD' => array(
            'Id' => 'DP-NOD',
            'Label' => 'No Duplication',
        ),
    );

}
