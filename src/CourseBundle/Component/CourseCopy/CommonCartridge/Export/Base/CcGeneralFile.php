<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_general.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base;

use DOMElement;
use XMLGenericDocument;

class CcGeneralFile extends XMLGenericDocument
{
    /**
     * Root element.
     *
     * @var DOMElement
     */
    protected $root;
    protected $rootns;
    protected $rootname;
    protected $ccnamespaces = [];
    protected $ccnsnames = [];

    public function __construct()
    {
        parent::__construct();

        foreach ($this->ccnamespaces as $key => $value) {
            $this->registerNS($key, $value);
        }
    }

    protected function onCreate(): void
    {
        $rootel = $this->appendNewElementNs(
            $this->doc,
            $this->ccnamespaces[$this->rootns],
            $this->rootname
        );
        // add all namespaces
        foreach ($this->ccnamespaces as $key => $value) {
            $dummy_attr = "{$key}:dummy";
            $this->doc->createAttributeNS($value, $dummy_attr);
        }

        // add location of schemas
        $schemaLocation = '';
        foreach ($this->ccnsnames as $key => $value) {
            $vt = empty($schemaLocation) ? '' : ' ';
            $schemaLocation .= $vt.$this->ccnamespaces[$key].' '.$value;
        }

        if (!empty($schemaLocation) && isset($this->ccnamespaces['xsi'])) {
            $this->appendNewAttributeNs(
                $rootel,
                $this->ccnamespaces['xsi'],
                'xsi:schemaLocation',
                $schemaLocation
            );
        }

        $this->root = $rootel;
    }
}
