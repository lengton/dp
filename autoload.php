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

// dp Class loader
spl_autoload_register (function ($class)
{
    $baseDir = __DIR__;

    $file = rtrim ($baseDir, '/').'/class/'.str_replace('\\', '/', ltrim ($class, "/\t\r\r\0\x0B ")).'.php';
    if (file_exists ($file))
    {
        require_once $file;
    } // dp Classes
});

// dp DB Class loader
spl_autoload_register (function ($class)
{
    $baseDir = __DIR__;

    $file = rtrim ($baseDir, '/').'/db/'.str_replace('\\', '/', ltrim ($class, "/\t\r\r\0\x0B ")).'.php';
    if (file_exists ($file))
    {
        require_once $file;
    } // dp DB Classes
});
