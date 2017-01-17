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

class DP extends dpPage
{
    function __construct ($config = false)
    {
        parent::__construct ($config);
    } // __construct


    public function start ()
    {
        // CHECK IF THIS IS RAN FROM THE WEBSERVER
        if (php_sapi_name () != 'cli')
        {
            // Render this page
            $this->render();
        } // Coming from where?
    } // start
} // DP
?>
