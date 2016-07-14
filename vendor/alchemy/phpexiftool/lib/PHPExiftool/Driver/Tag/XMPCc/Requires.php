<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPCc;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Requires extends AbstractTag
{

    protected $Id = 'requires';

    protected $Name = 'Requires';

    protected $FullName = 'XMP::cc';

    protected $GroupName = 'XMP-cc';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-cc';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Requires';

    protected $flag_List = true;

    protected $flag_Bag = true;

    protected $Values = array(
        'cc:Attribution' => array(
            'Id' => 'cc:Attribution',
            'Label' => 'Attribution',
        ),
        'cc:Copyleft' => array(
            'Id' => 'cc:Copyleft',
            'Label' => 'Copyleft',
        ),
        'cc:LesserCopyleft' => array(
            'Id' => 'cc:LesserCopyleft',
            'Label' => 'Lesser Copyleft',
        ),
        'cc:Notice' => array(
            'Id' => 'cc:Notice',
            'Label' => 'Notice',
        ),
        'cc:ShareAlike' => array(
            'Id' => 'cc:ShareAlike',
            'Label' => 'Share Alike',
        ),
        'cc:SourceCode' => array(
            'Id' => 'cc:SourceCode',
            'Label' => 'Source Code',
        ),
    );

}
