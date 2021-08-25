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
class ModificationPermissions extends AbstractTag
{

    protected $Id = 'P';

    protected $Name = 'ModificationPermissions';

    protected $FullName = 'PDF::TransformParams';

    protected $GroupName = 'PDF';

    protected $g0 = 'PDF';

    protected $g1 = 'PDF';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Modification Permissions';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'No changes permitted',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Fill forms, Create page templates, Sign',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Fill forms, Create page templates, Sign, Create/Delete/Edit annotations',
        ),
        false => array(
            'Id' => false,
            'Label' => 'Do not restrict applications to reader permissions',
        ),
        true => array(
            'Id' => true,
            'Label' => 'Restrict all applications to reader permissions',
        ),
    );

}
