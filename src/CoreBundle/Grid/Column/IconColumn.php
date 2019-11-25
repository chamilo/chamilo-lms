<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Grid\Column;

use APY\DataGridBundle\Grid\Column\Column;

/**
 * Class IconColumn.
 */
class IconColumn extends Column
{
    public function __initialize(array $params)
    {
        $params['filterable'] = false;
        $params['sortable'] = false;

        parent::__initialize($params);
    }

    public function getType()
    {
        return 'icon';
    }

    public function renderCell($value, $row, $router)
    {
        if ($value) {
            return "<img src=\"{{ '/".$value."' | apply_filter('small') }}\">";
        }

        return false;
    }
}
