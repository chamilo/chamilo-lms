<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Microsoft;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MediaClassSecondaryID extends AbstractTag
{

    protected $Id = 'WM/MediaClassSecondaryID';

    protected $Name = 'MediaClassSecondaryID';

    protected $FullName = 'Microsoft::Xtra';

    protected $GroupName = 'Microsoft';

    protected $g0 = 'QuickTime';

    protected $g1 = 'Microsoft';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Media Class Secondary ID';

    protected $Values = array(
        '00000000-0000-0000-0000-000000000000' => array(
            'Id' => '00000000-0000-0000-0000-000000000000',
            'Label' => 'Unknown Content',
        ),
        '00033368-5009-4AC3-A820-5D2D09A4E7C1' => array(
            'Id' => '00033368-5009-4AC3-A820-5D2D09A4E7C1',
            'Label' => 'Sound Clip from Game',
        ),
        '0B710218-8C0C-475E-AF73-4C41C0C8F8CE' => array(
            'Id' => '0B710218-8C0C-475E-AF73-4C41C0C8F8CE',
            'Label' => 'Home Video from Pictures',
        ),
        '1B824A67-3F80-4E3E-9CDE-F7361B0F5F1B' => array(
            'Id' => '1B824A67-3F80-4E3E-9CDE-F7361B0F5F1B',
            'Label' => 'Talk Show',
        ),
        '1FE2E091-4E1E-40CE-B22D-348C732E0B10' => array(
            'Id' => '1FE2E091-4E1E-40CE-B22D-348C732E0B10',
            'Label' => 'Video News',
        ),
        '3A172A13-2BD9-4831-835B-114F6A95943F' => array(
            'Id' => '3A172A13-2BD9-4831-835B-114F6A95943F',
            'Label' => 'Spoken Word',
        ),
        '44051B5B-B103-4B5C-92AB-93060A9463F0' => array(
            'Id' => '44051B5B-B103-4B5C-92AB-93060A9463F0',
            'Label' => 'Corporate Video',
        ),
        '6677DB9B-E5A0-4063-A1AD-ACEB52840CF1' => array(
            'Id' => '6677DB9B-E5A0-4063-A1AD-ACEB52840CF1',
            'Label' => 'Audio News',
        ),
        'A9B87FC9-BD47-4BF0-AC4F-655B89F7D868' => array(
            'Id' => 'A9B87FC9-BD47-4BF0-AC4F-655B89F7D868',
            'Label' => 'Feature Film',
        ),
        'B76628F4-300D-443D-9CB5-01C285109DAF' => array(
            'Id' => 'B76628F4-300D-443D-9CB5-01C285109DAF',
            'Label' => 'Home Movie',
        ),
        'BA7F258A-62F7-47A9-B21F-4651C42A000E' => array(
            'Id' => 'BA7F258A-62F7-47A9-B21F-4651C42A000E',
            'Label' => 'TV Show',
        ),
        'D6DE1D88-C77C-4593-BFBC-9C61E8C373E3' => array(
            'Id' => 'D6DE1D88-C77C-4593-BFBC-9C61E8C373E3',
            'Label' => 'Web-based Video',
        ),
        'E0236BEB-C281-4EDE-A36D-7AF76A3D45B5' => array(
            'Id' => 'E0236BEB-C281-4EDE-A36D-7AF76A3D45B5',
            'Label' => 'Audio Book',
        ),
        'E3E689E2-BA8C-4330-96DF-A0EEEFFA6876' => array(
            'Id' => 'E3E689E2-BA8C-4330-96DF-A0EEEFFA6876',
            'Label' => 'Music Video',
        ),
        'F24FF731-96FC-4D0F-A2F5-5A3483682B1A' => array(
            'Id' => 'F24FF731-96FC-4D0F-A2F5-5A3483682B1A',
            'Label' => 'Song from Game',
        ),
    );

}
