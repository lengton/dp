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

abstract class dpSessionEnabled extends dpSession
{
    private $dpSession = false;
    private $dp_session_key = false;

  
    public function __construct ($obj_param, $session_key = false)
    {
        if (is_object ($obj_param) && (get_class ($obj_param) == 'dpSession'))
        {
            // Get a copy of the session object, no need to call dpSession constructor
            $this->dpSession = $obj_param;
            if ($session_key)
                $this->dp_session_key = $session_key;
        } else {
             if (is_array ($obj_param))
                 parent::__construct ($obj_param);
        } // Did we get a dpSession object?
    } // __construct
    
    
    public function setSessionID ($object_id = false, $skip_check = false)
    {
        if ($this->dpSession && $this->dpSession->sid && $this->dp_session_key)
        {
            // Check if object_id exists
            if ($skip_check === false)
            {
                $select_data = array ($this->dp_session_key => $object_id);
                $object_id = false;
                if ($result = $this->select ($select_data))
                {
                    $object_data = $this->get_row ();
                    if (!empty ($object_data) && $object_data[$this->dp_session_key])
                        $object_id = $object_data[$this->dp_session_key];
                } // Do we have DB results?
            } // Query objecttable
            
            if (intVal ($object_id) > 0)
            {
                $this->dpSession->set (get_parent_class ($this).'_'.$this->dp_session_key, $object_id);
                return ($object_id);
            } // Set to session
        } // Has session Object?
        
        return (false);
    } // setSessionID
    
    
    public function getSessionID ($load_data = false)
    {
        if ($this->dpSession && $this->dpSession->sid && $this->dp_session_key)
        {
            $object_id = $this->dpSession->get (get_parent_class ($this).'_'.$this->dp_session_key);
            if ($load_data && $object_id)
            {
                if ($result = $this->select (array ($this->dp_session_key => $object_id)))
                {
                    if (!empty ($object_data = $this->get_row ()))
                        return ($object_data);
                } // Has object data?
            } // Do we need to auto load the object data?
            
            return ($object_id);
        } // Has session Object?
        
        return (false);
    } // setSessionUserID
    
} // dpSessionEnabled
?>