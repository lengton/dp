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

class dpUser extends dpSessionEnabled
{
    public $uid = false;


    public $table_def = array
    (
        'uid' => array (
                    'type' => 'serial',
                    'null' => false, 
                    'index' => true,
                    'unique' => true
                ),
        'login' => array (
                    'type' => 'varchar',
                    'length' => 64,
                    'null' => false,
                    'index' => true
                ),
        'password' => array (
                    'type' => 'varchar',
                    'length' => 128
                ),
        'email' => array (
                    'type' => 'varchar',
                    'length' => 128,
                ),
        'first_name' => array (
                    'type' => 'varchar',
                    'length' => 64,
                ),
        'last_name' => array (
                    'type' => 'varchar',
                    'length' => 96,
                ),                
        'middle_name' => array (
                    'type' => 'varchar',
                    'length' => 96,
                ),                
        'active' => array (
                    'type' => 'boolean',
                    'deafult' => 'true'
                ),
        'last_login' => array (
                    'type' =>'timestamp',
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
    
  
    public function __construct ($obj_param = false)
    {
        $this->table_name = __CLASS__;
        
        // Important!!! dpSessionEnabed objects should pass its db_field key to the parent constructor.
        // This associates the object's primary key field with the current session
        parent::__construct ($obj_param, 'uid');
    } // __construct
    
    
    public function verify_password ($password = false, $hash = false)
    {
        if ($hash)
            return (password_verify ($password, $hash));
        
        return (false);
    } // verify_password
    
    
    public function hash_password ($password = false)
    {
        if (($pass = trim ($password)) !== false)
            return (password_hash ($pass, PASSWORD_DEFAULT));
        
        return (false);
    } // hash_password
    
} // dpUser
?>