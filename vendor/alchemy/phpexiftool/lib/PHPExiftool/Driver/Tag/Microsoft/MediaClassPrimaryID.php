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
class MediaClassPrimaryID extends AbstractTag
{

    protected $Id = 'WM/MediaClassPrimaryID';

    protected $Name = 'MediaClassPrimaryID';

    protected $FullName = 'Microsoft::Xtra';

    protected $GroupName = 'Microsoft';

    protected $g0 = 'QuickTime';

    protected $g1 = 'Microsoft';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Media Class Primary ID';

    protected $Values = array(
        '01CD0F29-DA4E-4157-897B-6275D50C4F11' => array(
            'Id' => '01CD0F29-DA4E-4157-897B-6275D50C4F11',
            'Label' => 'Audio (not music)',
        ),
        'D1607DBC-E323-4BE2-86A1-48A42A28441E' => array(
            'Id' => 'D1607DBC-E323-4BE2-86A1-48A42A28441E',
            'Label' => 'Music',
        ),
        'DB9830BD-3AB3-4FAB-8A37-1A995F7FF74B' => array(
            'Id' => 'DB9830BD-3AB3-4FAB-8A37-1A995F7FF74B',
            'Label' => 'Video',
        ),
        'FCF24A76-9A57-4036-990D-E35DD8B244E1' => array(
            'Id' => 'FCF24A76-9A57-4036-990D-E35DD8B244E1',
            'Label' => 'Other (not audio or video)',
        ),
    );

}
