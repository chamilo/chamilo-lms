<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MinoltaRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class StorageMethod extends AbstractTag
{

    protected $Id = 18;

    protected $Name = 'StorageMethod';

    protected $FullName = 'MinoltaRaw::PRD';

    protected $GroupName = 'MinoltaRaw';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'MinoltaRaw';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Storage Method';

    protected $flag_Permanent = true;

    protected $Values = array(
        82 => array(
            'Id' => 82,
            'Label' => 'Padded',
        ),
        89 => array(
            'Id' => 89,
            'Label' => 'Linear',
        ),
    );

}
