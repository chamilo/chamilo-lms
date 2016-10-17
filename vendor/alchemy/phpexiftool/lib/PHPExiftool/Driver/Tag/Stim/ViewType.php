<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Stim;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ViewType extends AbstractTag
{

    protected $Id = 9;

    protected $Name = 'ViewType';

    protected $FullName = 'Stim::Main';

    protected $GroupName = 'Stim';

    protected $g0 = 'Stim';

    protected $g1 = 'Stim';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'View Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No Pop-up Effect',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Pop-up Effect',
        ),
    );

}
