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
 
class dpData extends dpObject
{
    public static $db = false;
    public $table_name = false;
    public $table_def = false;
    
    
    public function __construct ($config = false)
    {
        parent::__construct ($config);
        
        // OPEN DB CONNECTION
        if ($dbStr = $this->getConfig ('dbStr'))
        {
            if ((self::$db = @pg_pconnect ($dbStr)) === false)
            {
                $this->log ('DB connect failed.');
            } // Did it fail?
        } // Has DB string?
    } // __construct
    
    
    public function createTable ($drop_table = true)
    {
        if (self::$db === false)
            throw new Exception ('Not connected to any DB.');
        
        $params = array ('drop_table' => $drop_table);
        $sql = $this->generateSQL('create_table', $params);
        if (($this->res = pg_query (self::$db, $sql)) === false) 
        {
            $this->log (__METHOD__.': Sql query error: '.$sql);
        } // Do we have a query error?
    } // createTable
    
    
    public function generateSQL ($type = false, $params = false)
    {
        if (empty ($this->table_def) || (strlen ($this->table_name) < 1))
        {
            $this->log (__METHOD__.': No table name or definition');
            return (false);
        } // Check for table name and definition
        
        $sql = '';
        switch ($type)
        {
            case 'create_table' :
                if ($params && isset ($params['drop_table']))
                {
                    $sql .= 'DROP TABLE IF EXISTS '.$this->e ($this->table_name).'; ';
                } // drop table?
                
                $columns = array ();
                foreach ($this->table_def as $col => $def)
                {
                    $colstr = $col;
                    if (is_array ($def))
                    {
                        if (isset ($def['type']))
                            $colstr .= ' '.$def['type'];
                        if (isset ($def['null']))
                            $colstr .= ($def['null'] === false ? ' NOT NULL' : ' NULL');
                        if (isset ($def['default']))
                            $colstr .= ' DEFAULT '.$def['default'];
                    } // Has definition array?
                    $columns[] = $colstr;
                } // foreach
                $sql .= 'CREATE TABLE '.$this->table_name.' ('.implode (', ', $columns).');';
                break;
        } // switch
        
        return (strlen ($sql) ? $sql : false);
    } // generateSQL
    
    
    public function e ($str = false)
    {
        return (pg_escape_string ($str));
    } // e
} // dpData
?>