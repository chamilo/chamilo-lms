<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RIFF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AlphaPreprocessing extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'AlphaPreprocessing';

    protected $FullName = 'RIFF::ALPH';

    protected $GroupName = 'RIFF';

    protected $g0 = 'RIFF';

    protected $g1 = 'RIFF';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Alpha Preprocessing';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'none',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Level Reduction',
        ),
    );

}
