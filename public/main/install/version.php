<?php

/* For licensing terms, see /license.txt */
/**
 * This compatibility entry point exposes the Chamilo release metadata to the
 * installation and upgrade processes. The canonical metadata is stored at the
 * project root because the public install directory can be removed safely.
 */

return require dirname(__DIR__, 3).'/version.php';
