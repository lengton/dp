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
 
class dpUtilities extends dpObject
{
    public function __construct ($config = false)
    {
        parent::__construct ($config);
    } // __construct
    
    
    public function copyAppUtilities ($dpBase = false, $script_dir = false, $script_name = false)
    {
        if (strlen ($dpBase) && strlen ($script_name) && strlen ($script_dir))
        {
            $abpath = $dpBase.'/'.dpConstants::SCRIPT_DATADIR_APPBIN;
            if (file_exists ($abpath))
            {
                // Copy all files into app bin path with assigned app name
                if (($files = scandir ($abpath)) && !empty ($files))
                {
                    foreach ($files as $file)
                    {
                        // Skip dir
                        if (($file == '.') || ($file == '..'))
                            continue;
                            
                        if (is_file ($abpath.'/'.$file))
                        {
                            $dst_file = $script_dir.'/'.dpConstants::SCRIPT_DATADIR_BIN.'/'.$file;
                            $src_file = $abpath.'/'.$file;
                            
                            // Open source and destination files
                            if (($src = fopen ($src_file, 'r')) && ($dst = fopen ($dst_file, 'w')))
                            {
                                // Do the copy
                                while ($line = fgets ($src))
                                {
                                    if (strpos ($line, '$app_name') === 0)
                                        $line = '$app_name = \''.$script_name.'\';'.PHP_EOL;
                                    fputs ($dst, $line);
                                } // while
                                fclose ($src);
                                fclose ($dst);
                            } // Source opened?
                        } // Is this a file?
                    } // foreach
                } // Has files in directory?
            } // Has AppBin path?
        } // Has DP Base?
        return (false);
    } // copyAppUtilities
} // dpUtilities
?>