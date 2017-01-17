#!/usr/bin/env php
<?php
$app_name = '';
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

if (strlen ($app_name) < 1)
{
    echo 'App name missing';
    exit (1);
} // Check for app name

$app_path = substr (__DIR__, 0, (strpos (__DIR__, $app_name) + strlen ($app_name)));
include_once ($app_path);

// Setup dpAppBase
$dp->setConfig ('dpAppBase', $app_path.dpConstants::SCRIPT_DATADIR_SUFFIX);

// Register App level class autoloading
$dpPage = new dpPage ($config);
$dpPage->register_autoload ();

if ($argc == 2)
{
    try {
        $obj = new $argv[1];
        if (is_a ($obj, 'dpData'))
        {
            $obj->createTable ();
            echo 'Table "'.$obj->table_name.'" created.'.PHP_EOL;
        } else throw new Exception ('Not a derived class of dpData');
    } catch (Exception $e) {
        echo 'DB Error: '.$e->getMessage().PHP_EOL;
    }
} else {
    echo "\nUsage: $argv[0] <dpData class>\n\n";
    return 1;
} // Correct arguments?
