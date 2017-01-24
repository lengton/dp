#!/usr/bin/env php
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
$baseDir = substr (__DIR__, 0, strpos (__DIR__, '/bin'));
require $baseDir.'/loader.php';

$config = false;
$script_template = array (
    '<?php'.PHP_EOL,
    ':header',
    ':require',
    ':config',
    '$dp = new DP($config);'.PHP_EOL,
    '$dp->start();'.PHP_EOL,
    '?>'.PHP_EOL
);

$script_dirs = array (
    dpConstants::SCRIPT_DATADIR_BIN,
    dpConstants::SCRIPT_DATADIR_CACHE,
    dpConstants::SCRIPT_DATADIR_CLASS,
    dpConstants::SCRIPT_DATADIR_DB,
    dpConstants::SCRIPT_DATADIR_LOG,
    dpConstants::SCRIPT_DATADIR_PAGES,
    dpConstants::SCRIPT_DATADIR_TEMPLATES,
    dpConstants::SCRIPT_DATADIR_TEMP
);

function usage()
{
    global $argv;

    echo "\nUsage: $argv[0] app_name web_directory\n\n";
    return 1;
} // usage

if ($argc > 2)
{
    $dst = realpath ($argv[2]);
    if (strlen ($dst) && file_exists ($dst) && is_dir ($dst))
    {
        $script_name = $argv[1];
        if (strlen ($script_name) && (strpos ($script_name, '/') === false))
        {
            $script = $dst.'/'.$script_name;

            // Check if there's an existing script already
            if (file_exists ($script))
            {
                include_once $script;
            } // file exists?

            if ($fp = fopen ($script, 'w'))
            {
                // loader.php path
                if (($bpos = strpos (__DIR__, '/bin')) !== false)
                {
                    $dp_path = substr (__DIR__, 0, $bpos);
                    $loader_path = $dp_path.'/loader.php';
                    if (file_exists ($loader_path))
                    {
                        // For every line in our template
                        $same_app_dp_path = false;
                        foreach ($script_template as $str)
                        {
                            if ($str[0] == ':')
                            {
                                switch (substr ($str, 1))
                                {
                                    case 'header' :
                                        $str = '// DP Web Framework App Script'.PHP_EOL;
                                        break;

                                    case 'require' :
                                        // Determine if DP installation is within the same directory
                                        // If so, we use 'relative' path loading
                                        if (($bp_pos = strpos ($dp_path, dpConstants::DP_DIR)) !== false)
                                        {
                                            if (substr ($dp_path, 0, $bp_pos) == $dst)
                                            {
                                                $loader_path = '__DIR__.\''.dpConstants::DP_DIR.'/loader.php';
                                                $same_app_dp_path = true;
                                            } // DP and App path is the same
                                        } // check DP path
                                        $str = 'require_once '.($same_app_dp_path ? '' : '\'').$loader_path.'\';'.PHP_EOL;
                                        break;

                                    case 'config' :
                                        if (!isset ($config))
                                            $config = array ();

                                        // Needed default config values
                                        if (!isset ($config['php_commandline_path']))
                                        {
                                            $cmd_path = exec ('which php');
                                            if (trim ($cmd_path))
                                                $config['php_commandline_path'] = $cmd_path;
                                        } // Get PHP command line path

                                        // Do automatic config value modification, if needed
                                        fwrite ($fp, '$config = array ('.PHP_EOL);
                                        foreach ($config as $key => $value)
                                        {
                                            $raw_string = false;

                                            // Check if value is a match for DP parent path
                                            if ((gettype ($value) == 'string') &&
                                                (strpos ($value, ($ppath = dirname ($dp_path))) === 0) &&
                                                ($same_app_dp_path == true))
                                            {
                                                $raw_string = '__DIR__.\''.substr ($value, strlen ($ppath)).'\'';
                                            } // convert paths

                                            fwrite ($fp, '  \''.$key.'\' => ');
                                            if ($raw_string)
                                                fwrite ($fp, $raw_string);
                                            else fwrite ($fp, var_export ($value, true));
                                            fwrite ($fp, ','.PHP_EOL);
                                        } // foreach
                                        fwrite ($fp, ');'.PHP_EOL);
                                        continue 2;
                                } // switch
                            } // special key string?

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

                        // Create .htaccess
                        $dpu->createHTAccess($dst, $script_name);

                        echo "\nCreated '$script' on '$dst'\n\n";
                        return 0;
                    } else {
                        fclose ($fp);
                        @unlink ($script);
                        return 1;
                    }
                }
            } // File opened?
        } // Do we have a target script file?
    } // Script name
} // Have enough arguments
usage(); // Check for arguments
?>
