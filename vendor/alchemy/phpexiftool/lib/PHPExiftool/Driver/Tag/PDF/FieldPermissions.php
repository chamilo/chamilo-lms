<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PDF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FieldPermissions extends AbstractTag
{

    protected $Id = 'Action';

    protected $Name = 'FieldPermissions';

    protected $FullName = 'PDF::TransformParams';

    protected $GroupName = 'PDF';

    protected $g0 = 'PDF';

    protected $g1 = 'PDF';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Field Permissions';

    protected $Values = array(
        'All' => array(
            'Id' => 'All',
            'Label' => 'Disallow changes to all form fields',
        ),
        'Exclude' => array(
            'Id' => 'Exclude',
            'Label' => 'Allow changes to specified form fields',
        ),
        'Include' => array(
            'Id' => 'Include',
            'Label' => 'Disallow changes to specified form fields',
        ),
    );

}
