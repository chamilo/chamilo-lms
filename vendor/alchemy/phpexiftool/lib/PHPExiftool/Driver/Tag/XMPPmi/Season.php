<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPPmi;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Season extends AbstractTag
{

    protected $Id = 'season';

    protected $Name = 'Season';

    protected $FullName = 'XMP::pmi';

    protected $GroupName = 'XMP-pmi';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-pmi';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Season';

    protected $flag_Avoid = true;

    protected $Values = array(
        'fall' => array(
            'Id' => 'fall',
            'Label' => 'Fall',
        ),
        'spring' => array(
            'Id' => 'spring',
            'Label' => 'Spring',
        ),
        'summer' => array(
            'Id' => 'summer',
            'Label' => 'Summer',
        ),
        'winter' => array(
            'Id' => 'winter',
            'Label' => 'Winter',
        ),
    );

}
