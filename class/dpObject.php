<?php
/**
 * dp Web framework
 * Copyright (C) 2015-2017 Daniel G. Pamintuan II
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
            self::$dp_config = array_merge (self::$dp_config, $config);

        // Do we have external configs?
        // External config paths are always rooted at the current script directory
        if (isset (self::$dp_config['dp_external_config']))
        {
            // check for the proper string format
            $cval = self::$dp_config['dp_external_config'];
            if (($ldpos = strpos ($cval, ':')) !== false)
            {
                $var_name = trim (substr ($cval, 0, $ldpos));
                $ext_path = trim (substr ($cval, ($ldpos + 1)));
                if (($ext_path[0] != '/') && ($script_dir = $this->getConfig ('dp_script_dir')))
                {
                    $ext_path = $script_dir.'/'.$ext_path;
                    if (file_exists ($ext_path))
                    {
                        require_once ($ext_path);
                        if (isset ($$var_name) && is_array ($$var_name))
                        {
                            self::$dp_config = array_merge (self::$dp_config, $$var_name);
                            unset ($$var_name);
                        } // merge to config values
                    } // does the external config file exists?
                } // is a relative path?
            } // has delimeter?
        } // has external config path?

        // INITIALIZATIONS
        if ($tm = $this->getConfig ('dpTimezone'))
            date_default_timezone_set ($tm);
        else date_default_timezone_set (dpConstants::DP_TIMEZONE);
    } // __construct


    public function getConfig ($key = false)
    {
        if (strlen ($key))
        {
            if (isset (self::$dp_config[$key]))
                return (self::$dp_config[$key]);
            return false;
        }
        return (self::$dp_config);
    } // getConfig


    public function setConfig ($key = false, $value = false)
    {
        if (strlen ($key))
        {
            self::$dp_config[$key] = $value;
            return $value;
        }
        return false;
    } // setConfig


    public function createDirectories ($path = false, $file_path = false)
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
                } else return false;
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
                        } // file exists?
                    } // foreach

                    return true;
                } // Has dir elements?
            } // Has Raw path?
        } // Has path?

        return false;
    } // createDirectories


    public function log ($log = false)
    {
        syslog (LOG_NOTICE, $log);
    } // log

} // dpObject
?>
