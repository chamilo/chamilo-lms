<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XML;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class HyperlinksChanged extends AbstractTag
{

    protected $Id = 'HyperlinksChanged';

    protected $Name = 'HyperlinksChanged';

    protected $FullName = 'OOXML::Main';

    protected $GroupName = 'XML';

    protected $g0 = 'XML';

    protected $g1 = 'XML';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Hyperlinks Changed';

    protected $Values = array(
        false => array(
            'Id' => false,
            'Label' => 'No',
        ),
        true => array(
            'Id' => true,
            'Label' => 'Yes',
        ),
    );

}
