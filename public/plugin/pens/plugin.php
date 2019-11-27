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
 * Plugin definition file.
 *
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */

//the plugin title
$plugin_info['title'] = 'PENS';
//the comments that go with the plugin
$plugin_info['comment'] = "PENS implementation for Chamilo";
//the locations where this plugin can be shown
$plugin_info['location'] = [];
//the plugin version
$plugin_info['version'] = '1.1';
//the plugin author
$plugin_info['author'] = 'Guillaume Viguier-Just, Yannick Warnier';
$plugin_info = PENSPlugin::create()->get_info();
