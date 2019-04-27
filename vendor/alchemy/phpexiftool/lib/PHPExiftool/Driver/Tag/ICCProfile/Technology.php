<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ICCProfile;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Technology extends AbstractTag
{

    protected $Id = 'tech';

    protected $Name = 'Technology';

    protected $FullName = 'ICC_Profile::Main';

    protected $GroupName = 'ICC_Profile';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC_Profile';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Technology';

    protected $Values = array(
        'AMD ' => array(
            'Id' => 'AMD ',
            'Label' => 'Active Matrix Display',
        ),
        'CRT ' => array(
            'Id' => 'CRT ',
            'Label' => 'Cathode Ray Tube Display',
        ),
        'KPCD' => array(
            'Id' => 'KPCD',
            'Label' => 'Photo CD',
        ),
        'PMD ' => array(
            'Id' => 'PMD ',
            'Label' => 'Passive Matrix Display',
        ),
        'dcam' => array(
            'Id' => 'dcam',
            'Label' => 'Digital Camera',
        ),
        'dcpj' => array(
            'Id' => 'dcpj',
            'Label' => 'Digital Cinema Projector',
        ),
        'dmpc' => array(
            'Id' => 'dmpc',
            'Label' => 'Digital Motion Picture Camera',
        ),
        'dsub' => array(
            'Id' => 'dsub',
            'Label' => 'Dye Sublimation Printer',
        ),
        'epho' => array(
            'Id' => 'epho',
            'Label' => 'Electrophotographic Printer',
        ),
        'esta' => array(
            'Id' => 'esta',
            'Label' => 'Electrostatic Printer',
        ),
        'flex' => array(
            'Id' => 'flex',
            'Label' => 'Flexography',
        ),
        'fprn' => array(
            'Id' => 'fprn',
            'Label' => 'Film Writer',
        ),
        'fscn' => array(
            'Id' => 'fscn',
            'Label' => 'Film Scanner',
        ),
        'grav' => array(
            'Id' => 'grav',
            'Label' => 'Gravure',
        ),
        'ijet' => array(
            'Id' => 'ijet',
            'Label' => 'Ink Jet Printer',
        ),
        'imgs' => array(
            'Id' => 'imgs',
            'Label' => 'Photo Image Setter',
        ),
        'mpfr' => array(
            'Id' => 'mpfr',
            'Label' => 'Motion Picture Film Recorder',
        ),
        'mpfs' => array(
            'Id' => 'mpfs',
            'Label' => 'Motion Picture Film Scanner',
        ),
        'offs' => array(
            'Id' => 'offs',
            'Label' => 'Offset Lithography',
        ),
        'pjtv' => array(
            'Id' => 'pjtv',
            'Label' => 'Projection Television',
        ),
        'rpho' => array(
            'Id' => 'rpho',
            'Label' => 'Photographic Paper Printer',
        ),
        'rscn' => array(
            'Id' => 'rscn',
            'Label' => 'Reflective Scanner',
        ),
        'silk' => array(
            'Id' => 'silk',
            'Label' => 'Silkscreen',
        ),
        'twax' => array(
            'Id' => 'twax',
            'Label' => 'Thermal Wax Printer',
        ),
        'vidc' => array(
            'Id' => 'vidc',
            'Label' => 'Video Camera',
        ),
        'vidm' => array(
            'Id' => 'vidm',
            'Label' => 'Video Monitor',
        ),
    );

}
