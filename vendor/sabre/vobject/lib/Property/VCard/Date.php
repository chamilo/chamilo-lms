<?php

namespace Sabre\VObject\Property\VCard;

use
    Sabre\VObject\DateTimeParser;

/**
 * Date property
 *
 * This object encodes vCard DATE values.
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Date extends DateAndOrTime {

    /**
     * Returns the type of value.
     *
     * This corresponds to the VALUE= parameter. Every property also has a
     * 'default' valueType.
     *
     * @return string
     */
    public function getValueType() {

        return "DATE";

    }

}
