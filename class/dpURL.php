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

class dpURL extends dpObject
{
    public $valid = false;

    private $url_elements = false;
    private $url_parameters = false;

    private $script_name = false;
    private $request_uri = false;
    private $server_script = false;
    private $script_base = false;

    private $url_file_name = false;
    private $url_filetarget_pos = false;


    function __construct ($url = false)
    {
        $this->parseURL($url);
    } // __construct


    // Format for URL is /<app>/<dir>/<file>
    public function parseURL($url = false)
    {
        // Determine which URL to use
        $rawURL = false;
        if (strlen ($url))
            $rawURL = trim ($url);
        else if (isset ($_SERVER['REQUEST_URI'])){
            $rawURL = trim ($_SERVER['REQUEST_URI']);
            $this->request_uri = $rawURL;
        } // Which URL string?

        // Do we have a URL string?
        $url_str = false;
        if (strlen ($rawURL))
        {
            // EXTRACT SCRIPT FILENAME
            $this->server_script = $_SERVER['SCRIPT_FILENAME'];
            if (strlen ($this->server_script) && (($lspos = strrpos ($this->server_script, '/')) !== false))
            {
                $this->script_base = substr ($this->server_script, 0, $lspos).'/';
                $this->script_name = substr ($this->server_script, ($lspos + 1));

                // Copy this to global config
                $this->setConfig ('dpScriptName', $this->script_name);
                $this->setConfig ('dpScriptDir', $this->script_base);

                // Do we have the web_base on script string?
                if (($spos = strpos ($rawURL, $this->script_name)) !== false)
                {
                    $url_str = ltrim (substr ($rawURL, $spos + strlen ($this->script_name)), '/ ');

                    // Remove URL parameters
                    $url_str = $this->extractURLparameters ($url_str);
                    $this->url_elements = $this->processURL ($url_str);
                } // Is the script name in the URI?
            } // Has server script?
        } // Has URL string?

        return (false);
    } // parseURL


    private function extractURLparameters ($url_str = false)
    {
        if (strlen ($url_str) > 0)
        {
            if (($pstart = strpos ($url_str, '?')) !== false)
            {
                $pstr = substr ($url_str, ($pstart + 1));
                $url_str = substr ($url_str, 0, $pstart);

                // Extract GET parameters
                if (strlen ($pstr) > 0)
                {
                    $raw_params = explode ('&', $pstr);
                    if (!empty ($raw_params))
                    {
                        $url_params = array ();
                        foreach ($raw_params as $param)
                        {
                            $tuple = explode ('=', $param);
                            if (empty ($tuple) || (count ($tuple) != 2))
                                continue;

                            // Get Key-Value pair
                            $key = trim ($tuple[0]);
                            $val = $tuple[1];

                            // Overwrites previous definition
                            if (strlen ($key))
                                $url_params[$key] = $val;
                        } // foreach

                        if (!empty ($url_params))
                            $this->url_parameters = $url_params;
                    } // Not empty parameter items?
                } // Has parameter string?
            } // Do we have a start paramenter position?
        } // Is a valid $url_str?

        return ($url_str);
    } // extractURLparameters


    public function getURLElements ($url_info = false)
    {
        if (is_array ($url_info) && isset ($url_info['name']) && $this->url_elements)
        {
            $spliced = array();
            foreach ($this->url_elements as $url_item)
            {
                if ($url_item['name'] == $url_info['name'])
                    break;
                $spliced[] = $url_item;
            } // foreach

            return (empty ($spliced) ? false : $spliced);
        } // Do we have a URL item for splicing?

        return ($this->url_elements);
    } // getURLElements


    public function getPath ($index = false)
    {
        if (!empty ($this->url_elements))
        {
            // Where are we loading page content from?
            $path = '';
            if ($dpd = $this->getConfig ('dpAppBase'))
                $path = $dpd;
            else $path = $this->script_base.$this->script_name.dpConstants::SCRIPT_DATADIR_SUFFIX;   // By default
            $path = $path.'/'.dpConstants::SCRIPT_DATADIR_PAGES;

            foreach ($this->url_elements as $indx => $url_item)
            {
                if ($indx == 0)
                    continue;
                if (($index !== false) && ($indx == ($index + 1)))
                    break;

                $path .= '/'.$url_item['name'];
            } // foreach

            return ($path);
        } // Do we have URL elements?

        return (false);
    } // getPath


    public function getURLPath ($index = false)
    {
        if (!empty ($this->url_elements))
        {
            $url_path = '';
            foreach ($this->url_elements as $indx => $url_item)
            {
                if ($indx == 0)
                    continue;
                if (($index !== false) && ($indx == ($index + 1)))
                    break;

                $url_path .= '/'.$url_item['name'];
            } // foreach

            return ($url_path);
        } // Do we have URL elements?

        return (false);
    } // getURLPath


    private function processURL ($url_str = false)
    {
        if (strlen ($url_str) < 1)
            return (false);

        // Get URL elements
        if (strpos ($url_str, '/') !== false)
        {
            $url_elem = explode ('/', $url_str);
            if (empty ($url_elem))
                return (false);
        } else $url_elem = array ($url_str);

        // Where are we loading page content from?
        if ($dpd = $this->getConfig ('dpAppBase'))
            $base_dir = $dpd;
        else {
            $base_dir = $this->script_base.$this->script_name.dpConstants::SCRIPT_DATADIR_SUFFIX;   // By default
            $this->setConfig ('dpAppBase', $base_dir);
        } // Use default app base?

        // Process/Extract URL path
        $url_elements = array();
        $base_dir = $base_dir.'/'.dpConstants::SCRIPT_DATADIR_PAGES;
        $invalid = 0;

        // Add Root URL element
        $url_info = array (
            'name' => $this->script_name,
            'type' => 'script'
        );

        // Check if we have dpIncludes in the root directory
        if (is_file ($base_dir.'/'.dpConstants::DP_COMMON_INCLUDE))
            $url_info['has_include'] = true;

        // Add the script info
        $url_elements[] = $url_info;

        $file_name = $is_raw = false;
        foreach ($url_elem as $pos => $url_item)
        {
            $url_info = array();
            $base_dir .= '/'.$url_item;

            // Is this an dpInclude file?
            if ($url_item == dpConstants::DP_COMMON_INCLUDE)
                continue;

            // What kind of entity?
            if (is_dir ($base_dir))
            {
                $item_type = 'dir';

                // Check for magic switches
                if (file_exists ($base_dir.'/'.dpConstants::DP_SWITCH_RAWDIR))
                    $is_raw = true;

                // Check if we have dpIncludes in this directory
                if (is_file ($base_dir.'/'.dpConstants::DP_COMMON_INCLUDE))
                    $url_info['has_include'] = true;
            } else if (is_file ($base_dir)) {
                $item_type = 'file';
                $file_name = $url_item;       // Assign file types
                $this->url_filetarget_pos = ($pos + 1);  // And URL element position (0th is our script)
            } else {
                $item_type = 'none';
                $invalid++;
            } // if non-type, then update invalid count

            $url_info['name'] = $url_item;
            $url_info['type'] = $item_type;

            // dp Switches
            if ($is_raw)
                $url_info['raw'] = true;

            $url_elements[] = $url_info;
        } // foreach -- url items

        // Assign this URLs last file type name
        if ($file_name !== false)
            $this->url_file_name = $file_name;

        // Flag if URL is valid
        if ($invalid < 1)
            $this->valid = true;

        return ($url_elements);
    } // processURL


    public function getParam ($key = false)
    {
        if ((strlen ($key) < 0) || empty ($this->url_parameters) || !isset ($this->url_parameters[$key]))
            return (false);

        return ($this->url_parameters[$key]);
    } // getParam


    public function paramIsset ($key = false)
    {
        if (!empty ($this->url_parameters) && strlen ($key))
            return (isset ($this->url_parameters[$key]));

        return (false);
    } // paramIsset


    public function getURLTargetFileName ()
    {
        return ($this->url_file_name);
    } // getURLTargetFileName


    public function getURLTargetFileInfo ()
    {
        if (($this->url_filetarget_pos !== false) && !empty ($this->url_elements))
            return ($this->url_elements[$this->url_filetarget_pos]);

        return (false);
    } // getURLTargetFileInfo


    public function getURLClassName ($index = false)
    {
        if (!empty ($this->url_elements))
        {
            $class_name = '';
            foreach ($this->url_elements as $indx => $url_item)
            {
                if (($index !== false) && ($indx == ($index + 1)))
                    break;

                $class_name .= $url_item['name'].'_';
            } // foreach
            return (str_replace ('.', '_', trim ($class_name, '_')));
        } // Do we have it assigned?

        return (false);
    } // getURLClassName
} // dpURL
?>
