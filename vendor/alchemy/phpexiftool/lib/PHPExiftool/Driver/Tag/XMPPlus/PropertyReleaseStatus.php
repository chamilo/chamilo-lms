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
class PropertyReleaseStatus extends AbstractTag
{

    protected $Id = 'PropertyReleaseStatus';

    protected $Name = 'PropertyReleaseStatus';

    protected $FullName = 'XMP::plus';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Property Release Status';

    protected $Values = array(
        'PR-LPR' => array(
            'Id' => 'PR-LPR',
            'Label' => 'Limited or Incomplete Property Releases',
        ),
        'PR-NAP' => array(
            'Id' => 'PR-NAP',
            'Label' => 'Not Applicable',
        ),
        'PR-NON' => array(
            'Id' => 'PR-NON',
            'Label' => 'None',
        ),
        'PR-UPR' => array(
            'Id' => 'PR-UPR',
            'Label' => 'Unlimited Property Releases',
        ),
    );

}
