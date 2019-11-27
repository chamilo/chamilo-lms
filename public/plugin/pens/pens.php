<?php
/**
 * This file is part of chamilo-pens.
 *
 * chamilo-pens is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * chamilo-pens is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with chamilo-pens.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Provides an implementation of the PENS server, using the php-pens library.
 * This file must be required by a file pens.php accessible at the Chamilo root.
 *
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/lib/pens.php';
require_once __DIR__.'/chamilo_pens.php';

class ChamiloPackageHandler extends PENSPackageHandler
{
    public function processPackage($request, $path_to_package)
    {
        $server = PENSServer::singleton();
        // Moves the package to archive/pens
        $path_to_archives = api_get_path(SYS_ARCHIVE_PATH).'pens';
        if (!is_dir($path_to_archives)) {
            $mode = api_get_permissions_for_new_directories();
            mkdir($path_to_archives, $mode, true);
        }
        rename($path_to_package, $path_to_archives.'/'.$request->getFilename());
        // Insert the request in the database
        $chamilo_pens = new ChamiloPens($request);
        $chamilo_pens->save();
        $server->sendAlert($request, new PENSResponse(0, 'Package successfully processed'));
    }
}

$handler = new ChamiloPackageHandler();
$handler->setSupportedPackageTypes(['scorm-pif']);
$handler->setSupportedPackageFormats(['zip']);

$server = PENSServer::singleton();
$server->setPackageHandler($handler);

$server->receiveCollect();
