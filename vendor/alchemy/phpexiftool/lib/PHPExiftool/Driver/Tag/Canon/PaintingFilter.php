<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PaintingFilter extends AbstractTag
{

    protected $Id = 1537;

    protected $Name = 'PaintingFilter';

    protected $FullName = 'Canon::FilterInfo';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Painting Filter';

    protected $flag_Permanent = true;

    protected $Values = array(
        '-1' => array(
            'Id' => '-1',
            'Label' => 'Off',
        ),
    );

}
