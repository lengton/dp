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
    private $host_ip = false;
    private static $clientAccess = false;
    
    
    public $table_def = array
    (
        'sid' => array (
                    'type' => 'varchar',
                    'length' => 128, 
                    'null' => false, 
                    'index' => true
                ),
        'key' => array (
                    'type' => 'varchar',
                    'length' => 256, 
                    'null' => true, 
                    'index' => true, 
                    'default' => 'NULL'
                ),
        'value' => array (
                    'type' => 'text',
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
        parent::__construct ($config);
        $this->table_name = __CLASS__;

        self::$clientAccess = new dpClientAccess ();
        $this->startSession ();
    } // __construct
    
    
    public function startSession ()
    {
        // Get Remote Address
        $this->host_ip = @$_SERVER['REMOTE_ADDR'];
        
        // SET SESSION COOKIE
        if (isset ($_COOKIE['dpSID']) && false)
        {
            $this->sid = $_COOKIE['dpSID'];
            $this->log('Set dpSID='.$this->sid);
        } 
        else 
        {
            // Build session ID
            $this->sid = md5 (uniqid (mt_rand (), true)).md5 (uniqid (mt_rand (), true));
            setcookie ('dpSID', $this->sid, 0, '/');
            
            // COLLECT INFO
            if ($this->host_ip && self::$clientAccess)
            {
                // Update client access information
                $kv_pair = array (
                    'host_ip' => $this->host_ip, 
                    'browser' => @$_SERVER['HTTP_USER_AGENT'],
                    'modified' => 'NOW()'
                );
                self::$clientAccess->update ($kv_pair, array (dpConstants::DB_UPDATE_OR_INSERT => true));
            } // SAVE BROWSER INFO
        } // SET SESSION KEY
    } // startSession
} // dpSession
?>