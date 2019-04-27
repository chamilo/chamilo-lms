<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Palm;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PalmFileType extends AbstractTag
{

    protected $Id = 15;

    protected $Name = 'PalmFileType';

    protected $FullName = 'Palm::Main';

    protected $GroupName = 'Palm';

    protected $g0 = 'Palm';

    protected $g1 = 'Palm';

    protected $g2 = 'Document';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Palm File Type';

    protected $MaxLength = 8;

    protected $Values = array(
        '.pdfADBE' => array(
            'Id' => '.pdfADBE',
            'Label' => 'Adobe Reader',
        ),
        'BDOCWrdS' => array(
            'Id' => 'BDOCWrdS',
            'Label' => 'WordSmith',
        ),
        'BOOKMOBI' => array(
            'Id' => 'BOOKMOBI',
            'Label' => 'Mobipocket',
        ),
        'BVokBDIC' => array(
            'Id' => 'BVokBDIC',
            'Label' => 'BDicty',
        ),
        'DATALSdb' => array(
            'Id' => 'DATALSdb',
            'Label' => 'LIST',
        ),
        'DB99DBOS' => array(
            'Id' => 'DB99DBOS',
            'Label' => 'DB (Database program)',
        ),
        'DataPPrs' => array(
            'Id' => 'DataPPrs',
            'Label' => 'eReader',
        ),
        'DataPlkr' => array(
            'Id' => 'DataPlkr',
            'Label' => 'Plucker',
        ),
        'DataSprd' => array(
            'Id' => 'DataSprd',
            'Label' => 'QuickSheet',
        ),
        'DataTlMl' => array(
            'Id' => 'DataTlMl',
            'Label' => 'TealMeal',
        ),
        'DataTlPt' => array(
            'Id' => 'DataTlPt',
            'Label' => 'TealPaint',
        ),
        'InfoINDB' => array(
            'Id' => 'InfoINDB',
            'Label' => 'InfoView',
        ),
        'InfoTlIf' => array(
            'Id' => 'InfoTlIf',
            'Label' => 'TealInfo',
        ),
        'JbDbJBas' => array(
            'Id' => 'JbDbJBas',
            'Label' => 'JFile',
        ),
        'JfDbJFil' => array(
            'Id' => 'JfDbJFil',
            'Label' => 'JFile Pro',
        ),
        'Mdb1Mdb1' => array(
            'Id' => 'Mdb1Mdb1',
            'Label' => 'MobileDB',
        ),
        'PNRdPPrs' => array(
            'Id' => 'PNRdPPrs',
            'Label' => 'eReader',
        ),
        'PmDBPmDB' => array(
            'Id' => 'PmDBPmDB',
            'Label' => 'HanDBase',
        ),
        'SDocSilX' => array(
            'Id' => 'SDocSilX',
            'Label' => 'iSilo 3',
        ),
        'SM01SMem' => array(
            'Id' => 'SM01SMem',
            'Label' => 'SuperMemo',
        ),
        'TEXtREAd' => array(
            'Id' => 'TEXtREAd',
            'Label' => 'PalmDOC',
        ),
        'TEXtTlDc' => array(
            'Id' => 'TEXtTlDc',
            'Label' => 'TealDoc',
        ),
        'TdatTide' => array(
            'Id' => 'TdatTide',
            'Label' => 'Tides',
        ),
        'ToGoToGo' => array(
            'Id' => 'ToGoToGo',
            'Label' => 'iSilo',
        ),
        'ToRaTRPW' => array(
            'Id' => 'ToRaTRPW',
            'Label' => 'TomeRaider',
        ),
        'dataTDBP' => array(
            'Id' => 'dataTDBP',
            'Label' => 'ThinkDB',
        ),
        'vIMGView' => array(
            'Id' => 'vIMGView',
            'Label' => 'FireViewer (ImageViewer)',
        ),
        'zTXTGPlm' => array(
            'Id' => 'zTXTGPlm',
            'Label' => 'Weasel',
        ),
    );

}
