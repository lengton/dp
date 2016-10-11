<?php
/**
 * dp Web framework
 * Copyright (C) 2015 Daniel G. Pamintuan II
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

class dpClientAccess extends dpData
{
    public $table_def = array
    (
        'host_ip' => array (
                    'type' => 'varchar',
                    'length' => 40,
                    'null' => false,
                    'index' => true
                ),
        'browser' => array (
                    'type' => 'varchar',
                    'length' => 512,
                    'null' => true,
                    'default' => 'NULL'
                ),
        'created' => array (
                    'type' => 'timestamp',
                    'default' => 'NOW()',
                    'null' => false
                ),
        'modified' => array (
                    'type' => 'timestamp',
                    'default' => 'NOW()',
                    'null' => true
                )
    );


    public function __construct ($config = false)
    {
        $this->table_name = __CLASS__;
        parent::__construct ($config);
    } // __construct

} // dpClientAccess
?>
