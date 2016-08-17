<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ASF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ErrorCorrectionType extends AbstractTag
{

    protected $Id = 16;

    protected $Name = 'ErrorCorrectionType';

    protected $FullName = 'ASF::StreamProperties';

    protected $GroupName = 'ASF';

    protected $g0 = 'ASF';

    protected $g1 = 'ASF';

    protected $g2 = 'Video';

    protected $Type = 'binary';

    protected $Writable = false;

    protected $Description = 'Error Correction Type';

    protected $MaxLength = 16;

    protected $Values = array(
        '20FB5700-5B55-11CF-A8FD-00805F5C442B' => array(
            'Id' => '20FB5700-5B55-11CF-A8FD-00805F5C442B',
            'Label' => 'No Error Correction',
        ),
        'BFC3CD50-618F-11CF-8BB2-00AA00B4E220' => array(
            'Id' => 'BFC3CD50-618F-11CF-8BB2-00AA00B4E220',
            'Label' => 'Audio Spread',
        ),
    );

}
