#!/usr/bin/env php
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
$baseDir = substr (__DIR__, 0, strpos (__DIR__, '/bin'));
require $baseDir.'/autoload.php';

$config = false;
$script_template = array (
    "<?php\n",
    "// dp Web Framework app script\n",
    "require",
    "\$config = array ();\n\n",
    "\$dp = new DP(\$config);\n",
    "\$dp->start();\n",
    "?>\n"
);

$script_dirs = array (
    dpConstants::SCRIPT_DATADIR_BIN,
    dpConstants::SCRIPT_DATADIR_CACHE,
    dpConstants::SCRIPT_DATADIR_CLASS,
    dpConstants::SCRIPT_DATADIR_LOG,
    dpConstants::SCRIPT_DATADIR_PAGES,
    dpConstants::SCRIPT_DATADIR_TEMPLATES
);

if ($argc > 1)
{
    $dst = $argv[2];
    if (strlen ($dst) && file_exists ($dst) && is_dir ($dst))
    {
        $script_name = $argv[1];
        if (strlen ($script_name) && (strpos ($script_name, '/') === false))
        {
            $dst = rtrim ($dst, '/');
            $script = $dst.'/'.$script_name;

            // Check if there's an existing script already
            if (file_exists ($script))
            {
                include_once $script;
            }
            
            if ($fp = fopen ($script, 'w'))
            {
                // loader.php path
                if (($bpos = strpos (__DIR__, '/bin')) !== false)
                {
                    $loader_path = substr (__DIR__, 0, $bpos).'/loader.php';
                    if (file_exists ($loader_path))
                    {
                        foreach ($script_template as $str)
                        {
                            if (strpos ($str, 'require') !== false)
                            {
                                $str = "require_once '$loader_path';\n";
                            } // Require line?
                            
                            if ((strpos ($str, '$config') === 0) && !empty ($config))
                            {
                                fwrite ($fp, '$config = '.var_export ($config, true).';'.PHP_EOL);
                                continue;
                            } // In Config line?
                            
                            fwrite ($fp, $str);
                        } // foreach
                        fclose ($fp);
                        
                        // CREATE NEEDED SCRIPT DIRECTORIES
                        $script_dir = $script.dpConstants::SCRIPT_DATADIR_SUFFIX;
                        if (!file_exists ($script_dir))
                            mkdir ($script_dir);
                        foreach ($script_dirs as $dir)
                        {
                            $subdir = $script_dir.'/'.$dir;
                            if (!file_exists ($subdir))
                            {
                                mkdir ($subdir);
                                chmod ($subdir, 0775);
                                if (strcmp ($dir, dpConstants::SCRIPT_DATADIR_TEMPLATES) == 0)
                                {
                                    // Create template stub
                                    @touch ($subdir.'/'.$script_name);
                                } // Template dir?
                            } // Dir exists?
                        } // foreach
                        
                        // ADD .htaccess DENY ON BASE SCRIPT DIRECTORY
                        if ($fp = fopen ($script_dir.'/.htaccess', 'w'))
                        {
                            fwrite ($fp, "Deny From All\n");
                            fclose ($fp);
                        } // .htaccess file
                        
                        // COPY OVER NEEDED bin UTILITY SCRIPTS
                        $dpu = new dpUtilities ();
                        $dpu->copyAppUtilities ($baseDir, $script_dir, $script_name);
                        
                        echo "\nCreated '$script' on '$dst'.\nDon't forget to add or edit your .htaccess\n\n";
                        return 0;
                    } else {
                        fclose ($fp);
                        @unlink ($script);
                        return 1;
                    }
                }
            } // File opened?
        } // Do we have a target script file?
    } else {
        echo "\nUsage: $argv[0] app_name web_directory\n\n";
        return 1;
    } // Do we have a destination?
} // Check for arguments
?>