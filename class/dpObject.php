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
 
class dpObject
{
    const VERSION = '1.0';
    
    private static $dp_config = false;


    function __construct ($config = false)
    {
        if ((self::$dp_config === false) && isset ($GLOBALS['DP_GLOBALS']))
            self::$dp_config = $GLOBALS['DP_GLOBALS'];
        if ($config && is_array ($config))
        {
            self::$dp_config = array_merge (self::$dp_config, $config);
        } // Do we have config
        
        // INITIALIZATIONS
        if ($tm = $this->getConfig ('dpTimezone'))
            date_default_timezone_set ($tm);
        else date_default_timezone_set ('America/Chicago');
    } // __construct


    public function getConfig ($key = false)
    {
        if (strlen ($key))
        {
            if (isset (self::$dp_config[$key]))
                return (self::$dp_config[$key]);
            return (false);
        } 
        return (self::$dp_config);
    } // getConfig


    public function setConfig ($key = false, $value = false)
    {
        if (strlen ($key))
        {
            self::$dp_config[$key] = $value;
            return ($value);
        }
        return (false);
    } // setConfig
    
    
    public function createDirs ($path = false, $file_path = false)
    {
        if (strlen ($path) && ($path[0] == '/'))
        {
            $file_name = false;
            $rpath = $path;
            
            // Do we have the last element as a file?
            if ($file_path === true)
            {
                if (($sp = strrpos ($rpath, '/')) !== false)
                {
                    $file_name = substr ($rpath, ($sp + 1));
                    $rpath = ltrim (substr ($rpath, 0, $sp), dpConstants::URL_TRIM_STR);
                } else return (false);
            } // Extract file

            // Create path directories
            if (strlen ($rpath))
            {
                $de = explode ('/', $rpath);
                if (!empty ($de))
                {
                    $dirpath = '/';
                    $err = 0;
                    foreach ($de as $dir)
                    {
                        $dirpath .= $dir.'/';
                        $tdir = rtrim ($dirpath, dpConstants::URL_TRIM_STR);
                        if (!file_exists ($tdir))
                        {
                            if (@mkdir ($tdir))
                                @chmod ($tdir, 0775);
                            else $err++;
                        } // file exists?
                    } // foreach
                    
                    return ($err == 0 ? true : false);
                } // Has dir elements?
            } // Has Raw path?
        } // Has path?
        return (false);
    } // createDirs
    
    
    public function log ($log = false, $expand = false)
    {
        if ($expand)
            syslog (LOG_NOTICE, print_r ($log, true));
        else syslog (LOG_NOTICE, $log);
    } // log
} // dpObject
?>