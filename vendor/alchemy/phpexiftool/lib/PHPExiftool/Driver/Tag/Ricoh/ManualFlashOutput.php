<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Ricoh;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ManualFlashOutput extends AbstractTag
{

    protected $Id = 4108;

    protected $Name = 'ManualFlashOutput';

    protected $FullName = 'Ricoh::Main';

    protected $GroupName = 'Ricoh';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Ricoh';

    protected $g2 = 'Camera';

    protected $Type = 'rational64s';

    protected $Writable = true;

    protected $Description = 'Manual Flash Output';

    protected $flag_Permanent = true;

    protected $Values = array(
        '-288' => array(
            'Id' => '-288',
            'Label' => '1/64',
        ),
        '-240' => array(
            'Id' => '-240',
            'Label' => '1/32',
        ),
        '-216' => array(
            'Id' => '-216',
            'Label' => '1/22',
        ),
        '-192' => array(
            'Id' => '-192',
            'Label' => '1/16',
        ),
        '-168' => array(
            'Id' => '-168',
            'Label' => '1/11',
        ),
        '-144' => array(
            'Id' => '-144',
            'Label' => '1/8',
        ),
        '-120' => array(
            'Id' => '-120',
            'Label' => '1/5.6',
        ),
        '-96' => array(
            'Id' => '-96',
            'Label' => '1/4',
        ),
        '-72' => array(
            'Id' => '-72',
            'Label' => '1/2.8',
        ),
        '-48' => array(
            'Id' => '-48',
            'Label' => '1/2',
        ),
        '-24' => array(
            'Id' => '-24',
            'Label' => '1/1.4',
        ),
        0 => array(
            'Id' => 0,
            'Label' => 'Full',
        ),
    );

}
