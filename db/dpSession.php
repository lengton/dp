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

class dpSession extends dpData
{
    public $sid = false;
    
    
    public $table_def = array(
        'sid' => array ('type' => 'varchar(128)', 'null' => false, 'index' => true),
        'uid' => array ('type' => 'bigint', 'null' => true, 'index' => true),
        'key' => array ('type' => 'varchar(256)', 'null' => true, 'index' => true, 'default' => 'NULL'),
        'value' => array ('type' => 'text', 'null' => true, 'default' => 'NULL'),
        'created' => array ('type' => 'timestamp', 'default' => 'NOW()', 'null' => false),
        'modified' => array ('type' => 'timestamp', 'default' => 'NOW()', 'null' => true)
    );
    
  
    public function __construct ($config = false)
    {
        parent::__construct ($config);
        $this->table_name = __CLASS__;
    } // __construct
    
    
    public function setSID ($sid = false)
    {
        if (strlen ($sid))
            $this->sid = $sid;
    } // setSID    
} // dpSession
?>