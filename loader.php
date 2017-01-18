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

$GLOBALS['dp_classpath_cache'] = array ();
function dpClassLoader ($class, $class_path = false)
{
    if ($class_path == false)
        $class_path = __DIR__.'/class/';

    if ($tclass = trim ($class))
    {
        // Has this class been cached?
        if (isset ($GLOBALS['dp_classpath_cache'][$tclass]))
        {
            require_once $GLOBALS['dp_classpath_cache'][$tclass];
            return true;
        } // cached class?

        $class_file = $class_path.$tclass.'.php';
        if (file_exists ($class_file))
        {
            $GLOBALS['dp_classpath_cache'][$tclass] = $class_file;
            require_once $class_file;
            return true;
        }
        else
        {
            // Scan all possible class files
            if ($dir_items = scandir ($class_path))
            {
                foreach ($dir_items as $dir_entry)
                {
                    if (($dir_entry == '.') || ($dir_entry == '..') || !is_dir ($class_path.$dir_entry))
                        continue;

                    dpClassLoader ($class, $class_path.$dir_entry.'/');
                } // foreach
            } // has directory entries
        } // Require dp Classes
    } // has class name?

    return false;
} // dpClassLoader

// Register DP Class loader
spl_autoload_register ('dpClassLoader');


// Setup Environmental Variables
$dpVersion = '1.0';
$dpdir = dpConstants::DP_DIR;
$dpBase = rtrim (substr (__DIR__, 0, strpos (__DIR__, $dpdir)), dpConstants::URL_TRIM_STR).'/';

$DP_GLOBALS = array (
    'dpBase' => $dpBase,
    'dpDir' => $dpBase.trim ($dpdir, '/').'/',
    'dpVersion' => $dpVersion
);
?>
