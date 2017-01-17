<?php
/**
 * dp Web framework
 * Copyright (C) 2015-2017 Daniel G. Pamintuan II
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses.
 */

require  __DIR__.'/autoload.php';

// Figure out our Environment
$dpVersion = '1.0';
$dpdir = dpConstants::DP_DIR;
$dpBase = rtrim (substr (__DIR__, 0, strpos (__DIR__, $dpdir)), dpConstants::URL_TRIM_STR).'/';

$DP_GLOBALS = array (
    'dpBase' => $dpBase,
    'dpDir' => $dpBase.trim ($dpdir, '/').'/',
    'dpVersion' => $dpVersion
);
?>
