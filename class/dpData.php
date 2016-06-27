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
 
class dpData extends dpSQL
{
    public $table_name = false;
    public $table_def = false;
    public $table_cache = array ();
    protected $db_result = false;
    
    
    public function __construct ($config = false)
    {
        parent::__construct ($config);
        $this->processTableDefinition ();
    } // __construct


    public function processTableDefinition ()
    {
        if (!empty ($this->table_def) && is_array ($this->table_cache))
        {
            foreach ($this->table_def as $field => $def)
            {
                $this->table_cache['field_escape'][$field] = 
                    (in_array (strtolower ($def['type']), self::$sql_escape_types) ? true : false);
            } // foreach
        }
    } // processTableDefinition


    public function createTable ($drop_table = true)
    {
        $params = array ('drop_table' => $drop_table);
        return ($this->dpCallSQL('create_table', $params));
    } // createTable


    public function dpCallSQL ($type = false, $params = false)
    {
        if (empty ($this->table_def) || (trim ($this->table_name) == false))
        {
            $this->log (__METHOD__.': No table name or definition');
            return (false);
        } // Check for table name and definition
        
        $sql_method = dpConstants::SQL_METHOD_PREFIX.trim($type);
        if ((trim ($type) !== false) && method_exists ($this, $sql_method))
        {
            return ($this->$sql_method($params));
        } // SQL method exists?
        
        return (false);
    } // dpCallSQL

    
    public function update ($kv_pair, $params = false)
    {
        $this->db_result = false;
        if ($db_params = $this->select_helper ($kv_pair, $params))
        {
            $this->db_result = $this->dpCallSQL('update_table_row', $db_params);
            if ($this->db_result && ($this->dpCallSQL('affected_rows', $this->db_result) == 0))
            {
                if (!empty ($params) && isset ($params[dpConstants::DB_UPDATE_OR_INSERT]))
                    $this->db_result = $this->dpCallSQL('insert_table_row', $kv_pair);
            } // Do we need to insert on zero affected rows?
        } // Has table definition?

        return ($this->db_result);
    } // update
    
    
    public function insert ($kv_pair, $params = false)
    {
        $this->db_result = false;
        if ($this->table_def && is_array ($kv_pair) && !empty ($kv_pair))
        {
            foreach ($kv_pair as $key => $value)
            {
                if (strpos ($key, dpConstants::DP_DATA_OPERATOR_PREFIX) !== false)
                {
                    continue;
                } // Check if this is an operator -- where not using this here anyway
                
                if (isset ($this->table_def[$key]))
                {
                    $field_def = $this->table_def[$key];
                    
                    // Skipt this field from being inserted?
                    if ((isset ($field_def['auto_increment']) && $field_def['auto_increment']) ||
                        (strtolower ($field_def['type']) == 'serial'))
                        continue;
                } 
                else
                {
                    // Unset this as it doesn't exist for the table
                    unset ($kv_pair[$key]);
                } // Has the field
            } // foreach
            
            // Insert table
            $this->db_result = $this->dpCallSQL('insert_table_row', $kv_pair);
        } // Has table definition?

        return ($this->db_result);
    } // insert


    public function select ($kv_pair, $params = false)
    {
        $this->db_result = false;
        if ($db_params = $this->select_helper ($kv_pair, $params))
            $this->db_result = $this->dpCallSQL('select_table_row', $db_params);

        return ($this->db_result);
    } // select
    
    
    public function delete ($kv_pair, $params = false)
    {
        $this->db_result = false;
        if ($db_params = $this->select_helper ($kv_pair, $params))
            $this->db_result = $this->dpCallSQL('delete_table_row', $db_params);

        return ($this->db_result);
    } // select
    
    
    /*
     *  select_helper
     *
     *  $kv_pair array of key-value pair: array ('db_field' => value, ...)
     *  if db_field is an index, this is used in the 'where' clause
     *
     *  By default, where clause operator is '='. Changing operators can be done by inserting
     *  'opr_db_field' => SQL operator ('>', '<=', etc.) results to (db_field opr value)
     *
     *  Adding 'conj_db_field' => SQL conjuction ('AND', 'OR') changes the default 'AND' conjunction
     *  between the next where clause triplet.
     *
     *  $params optional parameters
     *  - dpConstants::DB_UPDATE_OR_INSERT: tries to update record, if none exists, then insert
     */
    private function select_helper (&$kv_pair, &$params)
    {
        $db_params = false;
        if ($this->table_def && is_array ($kv_pair) && !empty ($kv_pair))
        {
            $where_fields = array ();
            foreach ($kv_pair as $key => $value)
            {
                if (strpos ($key, dpConstants::DP_DATA_OPERATOR_PREFIX) !== false)
                {
                    continue;
                } // Check if this is an operator
                
                if (isset ($this->table_def[$key]))
                {
                    $field_def = $this->table_def[$key];
                    
                    // Harvest 'where' fields
                    if ((isset ($field_def['index']) && $field_def['index']) ||
                        (!empty ($params) && isset ($params['where']) && 
                            is_array($params['where']) && in_array ($key, $params['where'])))
                    {
                        $opr = '=';
                        if (isset ($kv_pair[dpConstants::DP_DATA_OPERATOR_PREFIX.$key]) &&
                            (trim ($kv_pair[dpConstants::DP_DATA_OPERATOR_PREFIX.$key]) !== false))
                            $opr = $kv_pair[dpConstants::DP_DATA_OPERATOR_PREFIX.$key];
                        $where_fields[$key] = array ('opr' => $opr, 'value' => $value);
                        
                        if (isset ($kv_pair[dpConstants::DP_DATA_CONJUNCTION_PREFIX.$key]) &&
                            (trim ($kv_pair[dpConstants::DP_DATA_CONJUNCTION_PREFIX.$key]) !== false))
                            $where_fields[$key]['conj'] = $kv_pair[dpConstants::DP_DATA_CONJUNCTION_PREFIX.$key];
                        continue;
                    } // Is this a 'where' or 'index' field?
                }
                else
                {
                    // Unset this as it doesn't exist for the table
                    unset ($kv_pair[$key]);
                } // Has the field
            } // foreach
            
            $db_params = array ('data' => $kv_pair);
            if (!empty ($where_fields))
                $db_params['where'] = $where_fields;
        } // Has table information?
        
        return ($db_params);
    } // select_helper

} // dpData
?>