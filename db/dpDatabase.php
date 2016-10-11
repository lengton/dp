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

class dpDatabase extends dpObject
{
    public static $pdo_db = NULL;
    private static $pdo_driver = false;
    protected $pdo_statement = NULL;


    public function __construct ($config = false)
    {
        parent::__construct ($config);

        // OPEN PDO DATABASE CONNECTION
        if ($db_dsn = $this->getConfig ('db_dsn'))
        {
            $db_user = $this->getConfig ('db_user');
            $db_password = $this->getConfig ('db_password');
            if (is_null (self::$pdo_db))
            {
                $pdo_options = null;
                if (($pdo_opt = $this->getConfig ('db_options')) && is_array ($pdo_opt))
                    $pdo_options = $pdo_opt;

                try
                {
                    self::$pdo_db = new PDO ($db_dsn, $db_user, $db_password, $pdo_options);
                    self::$pdo_driver = self::$pdo_db->getAttribute(PDO::ATTR_DRIVER_NAME);
                } catch (PDOException $e) {
                    throw new dpException('Database connection failed: '.$e->getMessage());
                } // Try to connect to database
            } // Do we have an existing connection?
        } // Has DSN string?
    } // __construct


    public function dpDB_create_table ($params = false)
    {
        if (is_null (self::$pdo_db))
            throw new dpException ('Not connected to any database.');

        $sql_statements = array ();
        if ($params && isset ($params['drop_table']))
            $sql_statements[] = 'DROP TABLE IF EXISTS '.$this->sqlEsc ($this->table_name).' CASCADE';

        $index_fields = array ();
        $columns = array ();
        foreach ($this->table_def as $col => $def)
        {
            // Quote column names for MySQL
            if (self::$pdo_driver  == dpConstants::DB_MYSQL_IDENT)
                $col = '`'.$col.'`';
            $colstr = $col;
            if (is_array ($def))
            {
                if (isset ($def['type']))
                {
                    $field_type = false;
                    if (is_array ($def['type']))
                    {
                        if (isset ($def['type'][self::$pdo_driver]))
                            $field_type = $def['type'][self::$pdo_driver];
                        else throw new dpException ('Database identification type "'.self::$pdo_driver.'" not found.');
                    } else $field_type = $def['type'];
                    if ($field_type)
                        $colstr .= ' '.$field_type;
                } // has type?

                if (isset ($def['unique']))
                    $colstr .= ' UNIQUE';

                if (isset ($def['length']))
                {
                    $field_length = false;
                    if (is_array ($def['length']))
                    {
                        if (isset ($def['type'][self::$pdo_driver]))
                            $field_length = $def['length'][self::$pdo_driver];
                        else throw new dpException ('Database identification length "'.self::$pdo_driver.'" not found.');
                    } else $field_length = $def['length'];
                    if ($field_length)
                        $colstr .= '('.$field_length.') ';
                } // length?
                if (isset ($def['null']))
                    $colstr .= ($def['null'] === false ? ' NOT NULL' : ' NULL');

                if (isset ($def['default']))
                    $colstr .= ' DEFAULT '.$def['default'];

                if (isset ($def['index']))
                    $index_fields[] = $col;

                if (isset ($def['foreign_key']) && is_array ($def['foreign_key']) && !empty ($def['foreign_key']))
                {
                    $fk_data = $def['foreign_key'];
                    if (isset ($fk_data['object']) && isset ($fk_data['field']) && ($obj = new $fk_data['object']))
                    {
                        $obj_field_def = $obj->table_def[$fk_data['field']];
                        if (!empty ($obj_field_def))
                        {
                            if (!isset ($obj_field_def['unique']))
                            {
                                throw new dpException ('Reference field "'.$fk_data['field'].'" must have UNIQUE constraint');
                            } // This field MUST be unique
                            $colstr .= ' REFERENCES '.$obj->table_name.' ('.$fk_data['field'].')';
                            if (isset ($fk_data['on']) && (trim ($fk_data['on']) !== false))
                                $colstr .= ' ON '.$fk_data['on'];
                        } else throw new dpException ('Reference table "'.$obj->table_name.'" field not found.');
                    } // Has Object in DP system?
                } // Has foreign key?
            } // Has definition array?
            $columns[] = $colstr;
        } // foreach
        $sql_statements[] = 'CREATE TABLE '.$this->table_name.' ('.implode (', ', $columns).')';

        if (!empty ($index_fields))
            $sql_statements[] = 'CREATE INDEX '.$this->table_name.'_index ON '.$this->table_name.' ('.implode (', ', $index_fields).')';

        return ($this->dpDB_query($sql_statements));
    } // dpDB_create_table


    public function dpDB_delete_table_row ($params = false)
    {
        if ($params && is_array ($params) && ($this->table_name !== false) &&
            isset ($params['where']) && isset ($params['data']))
        {
            list ($where_clause, $where_values) = $this->build_where_clause ($params['where']);
            $sql = 'DELETE FROM '.$this->table_name.$where_clause;
            return ($this->dpDB_query_params ($sql, $where_values));
        } // has params?

        return (false);
    } // dpSQL_delete_table_row


    public function dpDB_select_table_row ($params = false)
    {
        if ($params && is_array ($params) && ($this->table_name !== false) &&
            isset ($params['where']) && isset ($params['data']))
        {
            $select_fields = array ();
            foreach ($params['data'] as $field => $value)
            {
                if (self::$pdo_driver == dpConstants::DB_MYSQL_IDENT)
                    $field = '`'.$field.'`';
                $select_fields[] = $field;
            } // foreach

            $select_clause = '*';
            if (!empty ($select_fields))
                $select_clause = implode (', ', $select_fields);

            list ($where_clause, $where_values) = $this->build_where_clause ($params['where']);
            $sql = 'SELECT '.$select_clause.' FROM '.$this->table_name.$where_clause;
            return ($this->dpDB_query_params ($sql, $where_values));
        } // has params?

        return (false);
    } // dpDB_select_table_row


    public function dpDB_update_table_row ($params = false)
    {
        if ($params && is_array ($params) && ($this->table_name !== false) &&
            isset ($params['where']) && !empty ($params['where']) &&
            isset ($params['data']) && !empty ($params['data']))
        {
            $update_fields = array ();
            foreach ($params['data'] as $field => $value)
            {
                if (self::$pdo_driver == dpConstants::DB_MYSQL_IDENT)
                    $field = '`'.$field.'`';
                $update_fields[] = $field.'=?';
                $param_values[] = array ($value => $this->getDBfieldtype ($field));
            } // foreach

            if (!empty ($update_fields))
            {
                list ($where_clause, $where_values) = $this->build_where_clause ($params['where']);
                if (trim ($where_clause))
                    $sql = 'UPDATE '.$this->table_name.' SET '.implode (', ', $update_fields).$where_clause;

                return ($this->dpDB_query_params ($sql, array_merge ($param_values, $where_values)));
            } // Do we have update fields?
        } // has params?

        return (false);
    } // dpDB_update_table_row


    public function dpDB_insert_table_row ($params = false)
    {
        if ($params && is_array ($params) && ($this->table_name !== false))
        {
            $param_values = array ();
            $insert_fields = array ();
            $prepared_type = '';

            foreach ($params as $field => $value)
            {
                if (self::$pdo_driver == dpConstants::DB_MYSQL_IDENT)
                    $field = '`'.$field.'`';
                $insert_fields[] = $field;
                $param_values[] = array ($value => $this->getDBfieldtype ($field));
            } // foreach

            if (!empty ($insert_fields))
            {
                $sql = 'INSERT INTO '.$this->table_name.' ('.implode (', ', $insert_fields).') VALUES (';
                $field_count = count ($insert_fields);
                for ($indx = 0; $indx < $field_count; $indx++)
                {
                    $sql .= '?';
                    if ($indx < ($field_count - 1))
                        $sql .= ',';
                } // for
                $sql .= ')';
            } // Do we have update fields?

            return ($this->dpDB_query_params ($sql, $param_values));
        } // has params?
    } // dpDB_insert_table_row


    public function dpDB_fetch_row ($type = PDO::FETCH_ASSOC)
    {
        if ($this->pdo_statement)
            return ($this->pdo_statement->fetch ($type));

        return (NULL);
    } // dpDB_fetch_row


    public function dpDB_fetch_all_rows ($fetch_style = PDO::ATTR_DEFAULT_FETCH_MODE, $fetch_arg = NULL)
    {
        if ($this->pdo_statement)
            return ($this->pdo_statement->fetchAll ($fetch_style, $fetch_arg));

        return (NULL);
    } // dpDB_fetch_all_rows


    public function dpDB_table_exists ()
    {
        if ($this->table_name)
        {
            $sql = false;
            if (self::$pdo_driver == dpConstants::DB_MYSQL_IDENT)
                $sql = 'SHOW TABLES LIKE '.$this->sqlEsc ($this->table_name);
            if (self::$pdo_driver == dpConstants::DB_PGSQL_IDENT)
                $sql = 'SELECT tablename FROM pg_tables WHERE schemaname=\'public\'
                        AND tablename='.$this->sqlEsc ($this->table_name);

            // Has an SQL string?
            if ($sql)
            {
                $result = $this->dpDB_query ($sql);
                return (empty ($result) ? false : true);
            } // has SQL?
        } // has table name?

        return (false);
    } // dpDB_table_exists


    // SQL statement should be properly escaped when using this function
    public function dpDB_query ($sql = false)
    {
        if (is_null (self::$pdo_db))
            throw new dpException ('Not connected to database.');

        $sql_list = array ();
        $query_results = array ();
        if (is_string ($sql) && (trim ($sql) !==  false))
            $sql_list[] = $sql;
        else if (is_array ($sql) && !empty ($sql))
            $sql_list = &$sql;

        if (!empty ($sql_list))
        {
            if ($this->pdo_statement)
            {
                $this->pdo_statement->closeCursor ();
                $this->pdo_statement = NULL;
            } // Close existing statement, if any

            foreach ($sql_list as $sql_statement)
            {
                if ($this->pdo_statement = self::$pdo_db->query ($sql_statement))
                    $query_results[] = $this->pdo_statement->fetchAll ();
            } // foreach

            if (count ($query_results) == 1)
                return ($query_results[0]);
            return ($query_results);
        } // Has SQL statements?
    } // dpDB_query


    public function dpDB_query_params ($sql = false, $params = false)
    {
        if (is_null (self::$pdo_db) || (trim ($sql) === false))
            throw new dpException ('No SQL query or PDO resource.');

        if ($this->pdo_statement)
        {
            $this->pdo_statement->closeCursor ();
            $this->pdo_statement = NULL;
        } // Close existing statement, if any

        $driver_options = array ();
        if ($this->pdo_statement = self::$pdo_db->prepare ($sql, $driver_options))
        {
            // Bind values
            foreach ($params as $indx => $value_tuple)
            {
                list ($value, $type) = each ($value_tuple);
                $this->pdo_statement->bindValue (($indx + 1), $value, $type);
            } // foreach

            // Execute statement
            if ($this->pdo_statement->execute ())
                return ($this->pdo_statement);
        } // has mysql prepared statement?

        return (false);
    } // dpDB_query_params


    public function dpDB_affected_rows ()
    {
        if ($this->pdo_statement)
            return ($this->pdo_statement->rowCount ());

        return (false);
    } // dpDB_affected_rows


    protected function build_where_clause ($kv_where)
    {
        if (is_array ($kv_where) && !empty ($kv_where))
        {
            $value_list = array ();
            $where_clause = ' WHERE ';
            $field_count = count ($kv_where);
            $idx = 0;
            foreach ($kv_where as $field => $value_data)
            {
                $opr = trim ($value_data['opr']);
                $value = $value_data['value'];
                $conj = 'AND';

                if ($opr)
                {
                    if (self::$pdo_driver == dpConstants::DB_MYSQL_IDENT)
                        $field = '`'.$field.'`';
                    $where_clause .= '('.$field.$opr;
                    if (is_array ($value))
                        $where_clause .= '('.$indx.') ';
                    else $where_clause .= '?';
                    $where_clause .= ') ';

                    if (isset ($value_data['conj']))
                        $conj = trim ($value_data['conj']);

                    if (++$idx < $field_count)
                        $where_clause .= $conj.' ';

                    // Value list format: <field value> => <PDO constant type>
                    $value_list[] = array ($value => $this->getDBfieldtype ($field));
                } // Has operator?
            } // foreach

            return (array ($where_clause, $value_list));
        } // Has array values?

        return (false);
    } // build_where_clause


    protected function getDBfieldtype ($field = false)
    {
        if ($this->table_def && ($field = trim ($field)) && isset ($this->table_def[$field]))
        {
            if (isset ($this->table_def[$field]['type']))
            {
                $type = $this->table_def[$field]['type'];
                if (is_array ($type))
                {
                    if (isset ($type[self::$pdo_driver]))
                        $type = $type[self::$pdo_driver];
                    else $type = 'unknown';
                } // Is type an array?

                switch (strtolower ($type))
                {
                    case 'int' :
                    case 'bigint' :
                    case 'smallint' :
                    case 'serial' :
                        return (PDO::PARAM_INT);

                    case 'boolean' :
                    case 'bool' :
                        return (PDO::PARAM_BOOL);

                    case 'blob' :
                        return (PDO::PARAM_LOB);
                } // switch
            } // Has table definition?
        } // has objects?

        return (PDO::PARAM_STR);
    } // getDBfieldtype


    protected function sqlEsc ($str = false)
    {
        if (self::$pdo_db)
            return (self::$pdo_db->quote ($str));
        else throw new dbException ('No database resource.');
    } // sqlEsc

} // dpSQLcommon
?>
