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

class dpSQL extends dpObject
{
    public static $db = false;
    private static $dpSQL_query_cache = false;

    // SQL field types that needs to be escaped
    protected static $sql_escape_types = array ('varchar', 'text', 'timestamp');

        
    public function __construct ($config = false)
    {
        parent::__construct ($config);
        
        // OPEN DB CONNECTION
        if ($dbStr = $this->getConfig ('dbStr'))
        {
            if ((self::$db = @pg_pconnect ($dbStr)) === false)
            {
                $this->log ('DB connect failed.');
                throw new dpException('SQL DB connect failed.');
            } // Did it fail?
            
            $this->dpSQL_query_cache = array ();
        } // Has DB string?
    } // __construct
    
    
    public function dpSQL_create_table ($params = false)
    {
        $sql = '';
        if ($params && isset ($params['drop_table']))
        {
            $sql .= 'DROP TABLE IF EXISTS '.$this->sqlEsc ($this->table_name).'; ';
        } // drop table?
        
        $index_fields = array ();
        $columns = array ();
        foreach ($this->table_def as $col => $def)
        {
            $colstr = $col;
            if (is_array ($def))
            {
                if (isset ($def['type']))
                    $colstr .= ' '.$def['type'];
                if (isset ($def['len']))
                    $colstr .= '('.$def['len'].') ';
                if (isset ($def['null']))
                    $colstr .= ($def['null'] === false ? ' NOT NULL' : ' NULL');
                if (isset ($def['default']))
                    $colstr .= ' DEFAULT '.$def['default'];
                if (isset ($def['index']))
                    $index_fields[] = $col;
            } // Has definition array?
            $columns[] = $colstr;
        } // foreach
        $sql .= 'CREATE TABLE '.$this->table_name.' ('.implode (', ', $columns).');';
        
        if (!empty ($index_fields))
        {
            $sql .= 'CREATE INDEX '.$this->table_name.'_index ON '.$this->table_name.' ('.implode (', ', $index_fields).');';
        } // Do we have an index?
        
        return ($this->dpSQL_query($sql));
    } // dpSQL_create_table
    
    
    public function dpSQL_update_table_row ($params = false)
    {
        if ($params && is_array ($params) && ($this->table_name !== false) && 
            isset ($params['where']) && !empty ($params['where']) &&
            isset ($params['data']) && !empty ($params['data']))
        {
            $update_fields = array ();
            $indx = 1;
            foreach ($params['data'] as $field => $value)
            {
                $update_fields[] = $field.'=$'.($indx++);
                $param_values[] = $value;
            } // foreach
            
            if (!empty ($update_fields))
            {
                list ($where_clause, $where_values) = $this->build_where_clause ($params['where'], $indx);
                if (trim ($where_clause))
                    $sql = 'UPDATE '.$this->table_name.' SET '.implode (', ', $update_fields).$where_clause;

                return ($this->dpSQL_query_params ($sql, array_merge ($param_values, $where_values)));
            } // Do we have update fields?
        } // has params?
        
        return (false);
    } // dpSQL_update_table_row
    
    
    public function dpSQL_insert_table_row ($params = false)
    {
        if ($params && is_array ($params) && ($this->table_name !== false))
        {
            $param_values = array ();
            $insert_fields = array ();
            $indx = 1;
            foreach ($params as $field => $value)
            {
                $insert_fields[] = $field;
                $param_values[] = $value;
            } // foreach
            
            if (!empty ($insert_fields))
            {
                $ifc = count($insert_fields);
                $sql = 'INSERT INTO '.$this->table_name.' ('.implode (', ', $insert_fields).') VALUES (';
                for ($i = 0; $i < $ifc; $i++)
                {
                    $sql .= '$'.($i + 1);
                    if (($i + 1) < $ifc)
                        $sql .= ', ';
                } // for
                $sql .= ')'; 
            } // Do we have update fields?
            
            return ($this->dpSQL_query_params ($sql, $param_values));
        } // has params?
    } // dpSQL_insert_table_row
    
    
    protected function build_where_clause ($kv_where, &$indx = 1)
    {
        if (is_array ($kv_where) && !empty ($kv_where))
        {
            $value_list = array ();
            $where_clause = ' WHERE ';
            $field_count = count ($kv_where);
            $idx = 0;
            foreach ($kv_where as $key => $value_data)
            {
                $opr = trim ($value_data['opr']);
                $value = $value_data['value'];
                $conj = 'AND';
                
                if ($opr)
                {
                    $where_clause .= '('.$key.$opr;
                    if (is_array ($value))
                        $where_clause .= '('.$indx.') ';
                    else
                        $where_clause .= '$'.$indx;
                    $where_clause .= ') ';
                    
                    if (isset ($value_data['conj']))
                        $conj = trim ($value_data['conj']);
                    
                    if (++$idx < $field_count)
                        $where_clause .= $conj.' ';
                    
                    $value_list[] = $value;
                    $indx++;
                } // Has operator?
            } // foreach
            
            return (array ($where_clause, $value_list));
        } // Has array values?

        return (false);
    } // build_where_clause
    
    
    public function dpSQL_delete_table_row ($params = false)
    {
        if ($params && is_array ($params) && ($this->table_name !== false) && 
            isset ($params['where']) && isset ($params['data']))
        {
            list ($where_clause, $where_values) = $this->build_where_clause ($params['where']);
            $sql = 'DELETE FROM '.$this->table_name.$where_clause;
            return ($this->dpSQL_query_params ($sql, $where_values));
        } // has params?
        
        return (false);
    } // dpSQL_delete_table_row
    
    
    public function dpSQL_select_table_row ($params = false)
    {
        if ($params && is_array ($params) && ($this->table_name !== false) && 
            isset ($params['where']) && isset ($params['data']))
        {
            $select_fields = array ();
            foreach ($params['data'] as $field => $value)
                $select_fields[] = $field;
            
            $select_clause = '*';
            if (!empty ($select_fields))
                $select_clause = implode (', ', $select_fields);
            
            list ($where_clause, $where_values) = $this->build_where_clause ($params['where']);
            $sql = 'SELECT '.$select_clause.' FROM '.$this->table_name.$where_clause;
            return ($this->dpSQL_query_params ($sql, $where_values));
        } // has params?
        
        return (false);
    } // dpSQL_select_table_row
    
    
    public function dpSQL_fetch_row ($params = false)
    {
        if ($params && is_array ($params) && !empty ($params) && (pg_num_rows ($params['result']) > 0))
            return (pg_fetch_array ($params['result'], $params['row'], PGSQL_ASSOC));
        
        return (NULL);
    } // dpSQL_fetch_first_row
    
    
    public function dpSQL_fetch_all_rows ($params = false)
    {
        if ($params)
            return (pg_fetch_all ($params, PGSQL_ASSOC));
        
        return (NULL);
    } // dpSQL_fetch_first_row    
    
    
    public function dpSQL_query ($sql = false)
    {
        if (self::$db === false)
            throw new dpException ('Not connected to any DB.');
        
        $res = false;
        if (($res = pg_query (self::$db, $sql)) === false)
        {
             $this->log (__METHOD__.': Sql query error: '.$sql);
        } // Do we have an error?
        
        return ($res);
    } // dpSQL_query
    
    
    public function dpSQL_query_params ($sql = false, $params = false)
    {
        if (self::$db === false)
            throw new dpException ('Not connected to any DB.');
        
        $res = false;
        if (($res = pg_query_params (self::$db, $sql, $params)) === false)
        {
            $this->log (__METHOD__.': Sql query error: '.$sql.' => '.print_r ($params, true));
            throw new dpException (pg_last_error());
        }
        
        return ($res);
    } // dpSQL_query_params
    
    
    public function dpSQL_affected_rows ($res)
    {
        return (pg_affected_rows ($res));
    } // dpSQL_affected_rows
    
    
    protected function sqlEsc ($str = false)
    {
        return (pg_escape_string ($str));
    } // sqlEsc
    
} // dpSQL
?>