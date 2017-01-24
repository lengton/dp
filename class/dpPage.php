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

class dpPage extends dpData
{
    protected $dpURL = false;
    private static $dpTag = false;
    private static $dpTagLen = 0;
    private $template_data = false;
    private $page_object = false;
    private $config = false;

    private static $dp_classpath_cache = false;
    public $session = false;


    function __construct ($config = false, $url = false)
    {
        $this->config = $config;
        parent::__construct ($config);

        // Create URL object
        $this->dpURL = new dpURL ($url);

        // Initializations
        self::$dpTag = '<'.dpConstants::DP_TAG.':';
        self::$dpTagLen = strlen (self::$dpTag);
    } // __construct


    public function addClassPathCache ($class, $path)
    {
        if (self::$dp_classpath_cache === false)
            self::$dp_classpath_cache = array ();

        if ($tclass = trim ($class))
            self::$dp_classpath_cache[$class] = $path;
    } // addClassPathCache


    public function dpPageClassLoader ($class, $class_path = false)
    {
        if ($class_path == false)
            $class_path = $this->getConfig ('dpAppBase').'/'.dpConstants::SCRIPT_DATADIR_CLASS.'/';

        if ($tclass = trim ($class))
        {
            // Has this class been cached?
            if (self::$dp_classpath_cache && isset (self::$dp_classpath_cache[$tclass]))
            {
                require_once self::$dp_classpath_cache[$tclass];
                return true;
            } // cached class?

            $class_file = $class_path.$tclass.'.php';
            if (file_exists ($class_file))
            {
                self::$dp_classpath_cache[$tclass] = $class_file;
                require_once $class_file;
                return true;
            }
            else
            {
                // Scan all possible class files
                if ($dir_items = scandir ($class_path))
                {
                    foreach ($dir_items as $dir_entry)
                    {
                        if (($dir_entry == '.') || ($dir_entry == '..') || !is_dir ($class_path.$dir_entry))
                            continue;
                        if ($this->dpPageClassLoader ($class, $class_path.$dir_entry.'/'))
                            break;
                    } // foreach
                } // has directory entries
            } // Require dp Classes
        } // has class name?

        return false;
    } // dpPageClassLoader


    public function register_autoload ()
    {
        // REGISTER APP CLASSES AUTOLOAD FUNCTION
        self::$dp_classpath_cache = array ();
        spl_autoload_register (array ($this, 'dpPageClassLoader'));
    } // register_autoload


    private function get_milliseconds ()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((int) $sec * 1000 + ((float) $usec * 1000));
    } // get_milliseconds


    public function render ()
    {
        $out = '';
        $url_target_info = false;
        $render_start_time = $this->get_milliseconds ();

        // Render only if URL is valid
        if ($this->dpURL)
        {
            // REGISTER APP AUTOLOAD FUNCTION
            if (self::$dp_classpath_cache === false)
                $this->register_autoload ();

            // Get the URL file target (always a file not a directory)
            $url_target_info = $this->getInfo ('page_url_target_info');

            // Start Page Session
            $this->startSession ();

            // Load page elements, if any
            if (!empty ($url_target_info) && ($this->page_object = $this->loadPage (array ('url_target_info' => $url_target_info))))
            {
                // Call dpStart before anything else
                $out .= $this->page_object->callMethod (dpConstants::TAGNAME_STARTPAGE, true);

                // Does this page have a template? For Raw files, we force NO templates
                if (!isset ($url_target_info['raw']) && $this->loadTemplate ())
                {
                    // Do we have template elements?
                    if (!empty ($this->template_data))
                    {
                        foreach ($this->template_data as $template)
                        {
                            foreach ($template as $key => $value)
                            {
                                switch ($key)
                                {
                                    case 'text' :
                                        $out .= $value;
                                        break;

                                    case 'tag' :
                                        $out .= trim ($this->page_object->callMethod ($value, true));
                                        break;
                                } // switch
                            } // foreach
                        } // foreach
                    } // Non-empty template?
                } else {
                    // No template? Call the dpConstants::TAGNAME_DEFAULT 'dpDefault' method
                    $out = $this->page_object->callMethod (dpConstants::TAGNAME_DEFAULT, true);
                } // Has template?

                // Call dpEnd after everything else
                $out .= $this->page_object->callMethod (dpConstants::TAGNAME_ENDPAGE, true);
            } // Do we have page elements?
        } // URL valid?

        $render_stop_time = $this->get_milliseconds ();

        // Display render output
        $log_out = $this->getConfig ('dpScriptName').(($url_target_info && isset ($url_target_info['name'])) ? ': '.$url_target_info['name'] : '');
        $this->log (sprintf ('Page [%s] rendered in %.3fs', $log_out, ($render_stop_time - $render_start_time) / 1000));
        echo $out;
    } // render


    public function startSession ()
    {
        if ($this->session === false)
            $this->session = new dpSession ($this->config);
    } // startSession


    public function getInfo ($key)
    {
        $val = false;
        if (strlen ($key))
        {
            switch ($key)
            {
                case 'cache_template_path' :
                    $val = implode  ('/', array ($this->getConfig ('dpAppBase'), dpConstants::SCRIPT_DATADIR_CACHE, dpConstants::SCRIPT_DATADIR_TEMPLATES)).'/';
                    break;

                case 'template_path' :
                    $val = implode ('/', array ($this->getConfig ('dpAppBase'), dpConstants::SCRIPT_DATADIR_TEMPLATES)).'/';
                    break;

                case 'cache_page_path' :
                    $val = implode ('/', array ($this->getConfig ('dpAppBase'), dpConstants::SCRIPT_DATADIR_CACHE, dpConstants::SCRIPT_DATADIR_PAGES)).'/';
                    break;

                case 'page_path' :
                    $val = implode ('/', array ($this->getConfig ('dpAppBase'), dpConstants::SCRIPT_DATADIR_PAGES)).'/';
                    break;

                case 'class_path' :
                    $val = implode ('/', array ($this->getConfig ('dpAppBase'), dpConstants::SCRIPT_DATADIR_CLASS)).'/';
                    break;

                case 'page_url_path' :
                    if ($this->dpURL)
                        $val = $this->dpURL->getURLPath (false, true);
                    break;

                case 'page_fullpath' :
                    if ($this->dpURL)
                        $val = $this->dpURL->getPath (false, true);
                    break;

                case 'page_url_filename' :
                    if ($this->dpURL)
                        $val = $this->dpURL->getURLTargetFileName ();
                    break;

                case 'page_url_target_info' :
                    if ($this->dpURL)
                        $val = $this->dpURL->getURLTargetFileInfo ();
                    break;

                case 'page_class_name' :
                    if ($this->dpURL)
                        $val = $this->dpURL->getURLClassName (false, true);
                    break;

                case 'url_elements' :
                    if ($this->dpURL)
                        $val = $this->dpURL->getURLElements ();
                    break;
            } // switch
        } // Has key?

        return ($val);
    } // getInfo


    protected function loadTemplate ()
    {
        // Get cached copy of the processed template
        if ($tn = $this->getConfig ('dpTemplate'))
            $tp_name = ltrim ($tn, dpConstants::URL_TRIM_STR);
        else $tp_name = $this->getConfig ('dpScriptName');

        $cached_tp = $this->getInfo ('cache_template_path').$tp_name;
        $tp_file = $this->getInfo ('template_path').$tp_name;

        $exists_ct = file_exists ($cached_tp);
        $exists_tp = file_exists ($tp_file);

        // If trigger clear cache
        if ($this->getConfig ('dpClearStatCache') === true)
            clearstatcache();

        if ($exists_ct && $exists_tp && (filemtime ($tp_file) > filemtime ($cached_tp)))
        {
            $this->log ('Cache clear: '.$tp_name);
            $exists_ct = false;
            @unlink ($cached_tp);
        } // Remove cached template file?

        // Do we have an existing cached template?
        if ($exists_ct === false)
        {
            if ($this->createDirectories ($cached_tp, $file_path = true) === false)
                return (false);

            // Read and process template
            $this->template_data = $this->parseTemplate ($tp_name, $is_file = true);
            if ($fp = fopen ($cached_tp, 'w'))
            {
                fwrite ($fp, serialize ($this->template_data));
                fclose ($fp);
            } // Write to file
        } else {
            if (($str = file_get_contents ($cached_tp)) !== false)
                $this->template_data = unserialize ($str);
        } // Create/Read cached entry?

        return (true);
    } // loadTemplate


    private function parseTemplate ($template_str = false, $is_file = false)
    {
        $template_data = array ();
        if (strlen ($template_str = trim ($template_str)))
        {
            if ($is_file === true)
            {
                // Read template file
                $tp_file = $this->getInfo ('template_path').$template_str;
                if (file_exists ($tp_file) && ($fp = fopen ($tp_file, 'r')))
                {
                    $lines = array();
                    while (($line = fgets ($fp)) !== false)
                        $lines[] = $line;
                    fclose ($fp);
                } // File exists
            } else {
                $lines = explode ("\n", $template_str);
            } // Is the 'template_str' a file name?

            // Do we have line data?
            if (!empty ($lines))
            {
                $static_text = '';
                $pstate = dpConstants::PARSE_STATE_STATIC_TEXT;
                foreach ($lines as $line)
                {
                    $i = 0;
                    CHECKTAG:
                    if (($tpos = strpos ($line, self::$dpTag, $i)) !== false)
                    {
                        // End Static text accumulation
                        if ($i != $tpos)
                        {
                            $static_text .= substr ($line, $i, $tpos);
                            $template_data[] = array('text' => $static_text);
                            $static_text = '';
                        } // Save static text

                        $i = ($tpos + self::$dpTagLen);
                        $pstate =  dpConstants::PARSE_STATE_TAGNAME;
                        $tagparams = $tagname = '';
                        $line_len = strlen ($line);
                        while ($i < $line_len)
                        {
                            switch ($pstate)
                            {
                                case dpConstants::PARSE_STATE_TAGNAME:
                                    if (ctype_space ($line[$i]) || (strpos ('</>', $line[$i]) !== false))
                                    {
                                        $pstate = dpConstants::PARSE_STATE_TAGPARAMS;
                                        continue 2;
                                    } // End of tag name?
                                    $tagname .= $line[$i];
                                    break;

                                case dpConstants::PARSE_STATE_TAGPARAMS:
                                    if (strpos ('</>', $line[$i]) !== false)
                                    {
                                        $pstate = dpConstants::PARSE_STATE_ENDTAG;
                                        continue 2;
                                    } // End of tag?
                                    $tagparams .= $line[$i];
                                    break;

                                case dpConstants::PARSE_STATE_ENDTAG:
                                    if (strlen ($tagname))
                                    {
                                        $tag_data = array ('tag' => array ('name' => $tagname));
                                        $tag_params = $this->processTagParameters ($tagparams);
                                        if (!empty ($tag_params))
                                            $tag_data['tag']['params'] = $tag_params;
                                        $template_data[] = $tag_data;
                                        $tagname = '';
                                    } // Finalize data nugget

                                    if ($line[$i] == '>')
                                    {
                                        $i++;
                                        $pstate = dpConstants::PARSE_STATE_STATIC_TEXT;
                                        goto CHECKTAG;
                                    } // Start of tag?
                                    break;
                            } // switch
                            $i++;
                        } // while
                    } else {
                        if ($i == 0)
                            $static_text .= $line;
                        else $static_text .= substr ($line, $i);
                    } // Do we have dp tags?
                } // while

                if (strlen ($static_text))
                    $template_data[] = array ('text' => $static_text);
            } // Has main template file?
        } // Has template name?

        return ($template_data);
    } // parseTemplate


    protected function loadPage ($params = false)
    {
        // Get cached copy of the processed page
        $cached_pg = rtrim ($this->getInfo ('cache_page_path'), '/').$this->getInfo ('page_url_path');
        $page_path = $this->getInfo ('page_fullpath');
        $page_class_name = $this->getInfo ('page_class_name');

        // CHECK FOR PASSED PARAMETERS
        if (is_array ($params))
        {
            if (isset ($params['cache_path']) && strlen ($params['cache_path']))
                $cached_pg = $params['cache_path'];

            if (isset ($params['page_path']) && strlen ($params['page_path']))
                $page_path = $params['page_path'];

            if (isset ($params['page_class_name']) && strlen ($params['page_class_name']))
                $page_class_name = $params['page_class_name'];
        } // Do we have passed parameters?

        // Check if both cached version and the target file exists
        $exists_pt = file_exists ($cached_pg);
        $exists_pg = file_exists ($page_path);

        // If trigger clear cache
        if ($this->getConfig ('dpClearStatCache') === true)
            clearstatcache();

        if (!$exists_pg || ($exists_pg && $exists_pt && (filemtime ($page_path) > filemtime ($cached_pg))))
        {
            $this->log ('Cache clear: '.$page_path);
            $exists_pt = false;
            @unlink ($cached_pg);
        } // Remove cached template file?

        // Do we have an existing cached page class?
        if ($exists_pt === false)
        {
            if ($this->createDirectories ($cached_pg, $file_path = true) === false)
                return (false);

            // Read/process and write page data
            if ($fp = fopen ($cached_pg, 'w'))
            {
                if ($pe = $this->parsePage ($params))
                {
                    // Generate dpAppPage class
                    $this->generatePageClass ($fp, $pe, $params);

                    // CHECK IF GENERATED FILE IS VALID
                    if ($this->checkSyntax ($cached_pg))
                    {
                        @rename ($cached_pg, $cached_pg.'.error');
                        $exists_pt = false;
                    } else $exists_pt = true;
                }
                fclose ($fp);
            } // Write to file
        } // Write page data

        // Do we have a cached file?
        if ($exists_pt)
        {
            require_once $cached_pg;

            $class_name = dpConstants::DP_PAGE_CLASS_PREFIX.$page_class_name;
            return (new $class_name ($this));
        } // Require page file

        return (false);
    } // loadPage


    protected function checkSyntax ($php_path = false)
    {
        $php_commandline_path = $this->getConfig ('php_commandline_path');
        if ($php_path && $php_commandline_path && file_exists ($php_commandline_path))
        {
            $output = array();
            $exec_str = $php_commandline_path.' -l '.$php_path;
            @exec ($exec_str, $output, $res);
            if ($res)
                $this->log ('Syntax Error: '.$this->getInfo ('page_fullpath'));
            return ($res);
        } // Has needed data?
        return (false);
    } // checkSyntax


    private function generatePageClass ($fp = false, $pe = false, $params = false)
    {
        if (is_resource ($fp) && is_array ($pe))
        {
            $class_name = $this->getInfo ('page_class_name');

            // CHECK FOR PASSED PARAMETERS
            if (is_array ($params))
            {
                if (isset ($params['page_class_name']) && strlen ($params['page_class_name']))
                    $class_name = $params['page_class_name'];
            } // Has parameters?

            fwrite ($fp, dpConstants::PHP_TAG_START.PHP_EOL);
            fwrite ($fp, dpConstants::DP_PAGE_CLASS_HEADER.': '.$class_name.PHP_EOL);
            fwrite ($fp, 'class '.dpConstants::DP_PAGE_CLASS_PREFIX.$class_name." extends dpAppPage\n{\n");

            // Build page class methods
            foreach ($pe as $tag => $tag_items)
            {
                $method_type = 'public function ';
                fwrite ($fp, dpConstants::DP_PAGE_CLASS_INDENT.$method_type.dpConstants::DP_PAGE_CLASS_FUNC_PREFIX.$tag."()\n");
                fwrite ($fp, dpConstants::DP_PAGE_CLASS_INDENT."{\n");

                fwrite ($fp, dpConstants::DP_PAGE_CLASS_INDENT.dpConstants::DP_PAGE_CLASS_INDENT.'$dpArgs=array();'."\n");
                fwrite ($fp, dpConstants::DP_PAGE_CLASS_INDENT.dpConstants::DP_PAGE_CLASS_INDENT.'if ((func_num_args() > 0) && ($dp_args=func_get_arg(0)) && !empty($dp_args)) $dpArgs=$dp_args;'."\n\n");

                foreach ($tag_items as $tag_item)
                {
                    foreach ($tag_item as $item_type => $item_data)
                    {
                        switch ($item_type)
                        {
                            case 'code' :
                                $lines = explode ("\n", $item_data);
                                foreach ($lines as $line)
                                {
                                    fwrite ($fp, dpConstants::DP_PAGE_CLASS_INDENT.dpConstants::DP_PAGE_CLASS_INDENT);
                                    fwrite ($fp, $line.PHP_EOL);
                                } // foreach
                                break;

                            case 'static' :
                            default :
                                if (($pe = $this->parseTemplate ($item_data)) && !empty ($pe))
                                {
                                    $pe_count = count ($pe);
                                    foreach ($pe as $ppos => $pitem)
                                    {
                                        fwrite ($fp, dpConstants::DP_PAGE_CLASS_INDENT.dpConstants::DP_PAGE_CLASS_INDENT);
                                        foreach ($pitem as $ptag => $pdata)
                                        {
                                            switch ($ptag)
                                            {
                                                case 'text' :
                                                    fwrite ($fp, 'echo "'.str_replace ('"', '\"', trim ($pdata)).'";'.PHP_EOL);
                                                    break;

                                                case 'tag' :
                                                    // GENRATE DP TAG CODE
                                                    switch ($pdata['name'])
                                                    {
                                                        // Output tag parameter
                                                        case 'param' :
                                                            if (isset ($pdata['params']) && !empty ($pdata['params']) && isset ($pdata['params']['name']))
                                                            {
                                                                if (($lv_name = trim ($pdata['params']['name'])) !== false)
                                                                    fwrite ($fp, 'echo (isset($dpArgs[\''.$lv_name.'\'])?$dpArgs[\''.$lv_name.'\']:"");'.PHP_EOL);
                                                            } // We need parameters for this
                                                            break;

                                                        // Output PHP variable
                                                        case 'var' :
                                                            if (isset ($pdata['params']) && !empty ($pdata['params']) && isset ($pdata['params']['name']))
                                                            {
                                                                if (($lv_name = trim ($pdata['params']['name'])) !== false)
                                                                    fwrite ($fp, 'echo (isset($'.$lv_name.')?$'.$lv_name.':"");'.PHP_EOL);
                                                            } // We need parameters for this
                                                            break;

                                                        default :
                                                            fwrite ($fp, 'echo $this->getValue (\''.addslashes ($pdata['name']).'\'');
                                                            if (isset ($pdata['params']) && !empty ($pdata['params']))
                                                            {
                                                                $params = array();
                                                                foreach ($pdata['params'] as $key => $value)
                                                                    $params[] = "'".addslashes($key)."'=>'".addslashes($value)."'";
                                                                fwrite ($fp, ', array('.implode (',', $params).')');
                                                            } // Have parameters?
                                                            fwrite ($fp, ');'.PHP_EOL);
                                                    } // switch
                                            } // switch
                                        } // foreach -- elements
                                    } // foreach -- item
                                } // Has parsed elements?
                                break;
                        } // switch
                    } // foreach -- items
                } // foreach -- tags

                fwrite ($fp, dpConstants::DP_PAGE_CLASS_INDENT.'} // '.dpConstants::DP_PAGE_CLASS_FUNC_PREFIX.$tag."\n\n");
            } // foreach

            fwrite ($fp, '} // '.dpConstants::DP_PAGE_CLASS_PREFIX.$class_name."\n");
            fwrite ($fp, dpConstants::PHP_TAG_END.PHP_EOL);

            return (true);
        } // Has resources?

        return (false);
    } // generatePageClass


    private function parsePage ($params = false)
    {
        $page_elements = false;
        $pg_file = $this->getInfo ('page_fullpath');

        // Setup switches
        $add_default = false;

        // Modify switches
        if (is_array ($params))
        {
            // Is this a raw path?
            if (isset ($params['url_target_info']) && isset ($params['url_target_info']['raw']))
                $add_default = true;

            // Do we need to parse an external file?
            if (isset ($params['page_path']) && strlen ($params['page_path']))
                $pg_file = $params['page_path'];
        } // Has URL target?

        // Read page file
        if (file_exists ($pg_file) && ($fp = fopen ($pg_file, 'r')))
        {
            // Read all the file -- First pass so we can pre-process
            $file_lines = array ();
            $has_default_tag = 0;
            while (($line = fgets ($fp)) !== false)
            {
                // Ignore comments
                if ($line[0] == '#') continue;

                $file_lines[] = $line;
                if (strlen ($line) && (strpos ($line, ':'.dpConstants::TAGNAME_DEFAULT) === 0))
                    $has_default_tag++;
            } // while
            fclose ($fp);

            // DO WE HAVE TAGS? IF NOT, THEN ADD DEFAULT TAG
            if ($add_default && ($has_default_tag === 0))
                array_unshift ($file_lines, ':'.dpConstants::TAGNAME_DEFAULT.PHP_EOL);

            $page_elements = array ();
            $pstate = dpConstants::PARSE_STATE_TAGLINE;
            $tagname = $tagbody = '';
            foreach ($file_lines as $line)
            {
                switch ($pstate)
                {
                    case dpConstants::PARSE_STATE_TAGBODY:
                        if ($line[0] == ':')
                        {
                            if (($pbody = $this->processTagBody ($tagbody)) && !empty ($pbody) && strlen ($tagname))
                                $page_elements[$tagname] = $pbody;
                            goto FINDTAGNAME;
                        } // Start of next tag?
                        $tagbody .= $line;
                        break;

                    case dpConstants::PARSE_STATE_TAGLINE:
                        // Skip blank lines
                        if (strlen (trim ($line)) < 1)
                            continue 2;

                        if ($line[0] != ':')
                            continue 2;

                        FINDTAGNAME:
                        $pstate = dpConstants::PARSE_STATE_FINDTAGNAME;

                    default:
                        $i = 0;
                        $line_len = strlen ($line);
                        while ($i < $line_len)
                        {
                            switch ($pstate)
                            {
                                case dpConstants::PARSE_STATE_FINDTAGNAME:
                                    if (ctype_space ($line[$i]) !== true)
                                    {
                                        $tagname = '';
                                        $pstate = dpConstants::PARSE_STATE_TAGNAME;
                                        continue;
                                    }
                                    break;

                                case dpConstants::PARSE_STATE_TAGNAME:
                                    if (ctype_space ($line[$i]))
                                    {
                                        $tagbody = substr ($line, ($i + 1));
                                        $pstate = dpConstants::PARSE_STATE_TAGBODY;
                                        continue 4;
                                    } // End of tag name?
                                    $tagname .= $line[$i];
                                    break;
                            } // switch
                            $i++;
                        } // while
                } // switch
            } // while

            // Do we have trailing data?
            if (strlen ($tagbody))
            {
                if (($pbody = $this->processTagBody ($tagbody)) && !empty ($pbody) && strlen ($tagname))
                    $page_elements[$tagname] = $pbody;
            } // Parse tag body
        } // Has main template file?

        return ($page_elements);
    } // parsePage


    private function processTagBody ($body = false)
    {
        $body_elements = false;

        if (strlen ($body))
        {
            $lpos = $i = 0;
            $body_len = strlen ($body);
            $pts_len = strlen (dpConstants::PHP_TAG_START);
            $pte_len = strlen (dpConstants::PHP_TAG_END);
            $pstate = dpConstants::PARSE_STATE_STATIC_TEXT;
            $static_text = '';
            $quote_level = 0;
            $body_elements = array ();

            while ($i < $body_len)
            {
                switch ($pstate)
                {
                    case dpConstants::PARSE_STATE_STATIC_TEXT:
                        if ($body[$i] == '<')
                        {
                            // Is this a PHP tag?
                            if ((($i + $pts_len) < $body_len) && (dpConstants::PHP_TAG_START == substr ($body, $i, $pts_len)))
                            {
                                // Static text?
                                if ($i != $lpos)
                                    $body_elements[] = array ('static' => trim (substr ($body, $lpos, ($i - $lpos))));

                                $lpos = ($i + $pts_len); // Start just after the PHP start tag
                                $pstate = dpConstants::PARSE_STATE_PHPCODE;
                                continue 2;
                            } // Start PHP tag?
                        } // check for PHP tag
                        break;

                    case dpConstants::PARSE_STATE_PHPCODE:
                        if (strpos ("'\"", $body[$i]) !== false)
                        {
                            $quote_level++;
                            $pstate = dpConstants::PARSE_STATE_IN_QUOTE;
                            $i++;
                            continue 2;
                        } // In quote?

                        if ($body[$i] == '?')
                        {
                            if ((($i + $pte_len) < $body_len) && (dpConstants::PHP_TAG_END == substr ($body, $i, $pte_len)))
                            {
                                $body_elements[] = array ('code' => trim (substr ($body, $lpos, ($i - $lpos))));
                                $i += $pte_len; // Now move past the PHP end tag
                                $lpos = $i;
                                $pstate = dpConstants::PARSE_STATE_STATIC_TEXT;
                                continue 2;
                            } // End PHP tag?
                        } // check of PHP end tag?
                        break;

                    case dpConstants::PARSE_STATE_IN_QUOTE:
                        // Check of escapes
                        if ($body[$i] == "\\")
                        {
                            // Skip quote
                            if ((($i + 1) < $body_len) && (strpos ("'\"", $body[$i + 1]) !== false))
                            {
                                $i += 2;
                                continue 2;
                            }
                        } // Escaped?

                        // Did we reach the end of the quote?
                        if (strpos ("'\"", $body[$i]) !== false)
                        {
                            $quote_level--;
                            $pstate = dpConstants::PARSE_STATE_PHPCODE;
                        }
                        break;
                } // switch
                $i++;
            } // while

            // Do we have trailing data?
            if (($lpos != $i) && ($quote_level == 0))
            {
                $data = trim (substr ($body, $lpos, ($i - $lpos)));
                $key = 'static';
                if ($pstate === dpConstants::PARSE_STATE_PHPCODE)
                {
                    $key = 'code';
                    if (substr ($data, -($pte_len)) == dpConstants::PHP_TAG_END)
                        $data = substr ($data, 0, strlen ($data) - $pte_len);
                }
                $body_elements[] = array ($key => $data);
            } // Does pointers differ?
        } // Has string length?

        return ($body_elements);
    } // processTagBody


    private function processTagParameters ($param_string)
    {
        $params = array ();
        if ($pstr = trim ($param_string))
        {
            $i = 0;
            $quote_char = $key = $value = '';
            $pstate = dpConstants::PARSE_STATE_PARAM_NAME;
            while ($i < strlen ($pstr))
            {
                switch ($pstate)
                {
                    case dpConstants::PARSE_STATE_PARAM_NAME:
                        if ($pstr[$i] == '=')
                        {
                            $i++;
                            while (ctype_space ($pstr[$i])) $i++;  // Eat up spaces
                            $pstate = dpConstants::PARSE_STATE_PARAM_VALUE;
                            continue 2;
                        } // Reached equal sign?
                        $key .= $pstr[$i];
                        break;

                    case dpConstants::PARSE_STATE_PARAM_VALUE:
                        if (strpos ("\"'", $pstr[$i]) !== false)
                        {
                            $quote_char = $pstr[$i];
                            $i++;
                            $value = '';
                            $pstate = dpConstants::PARSE_STATE_IN_QUOTE;
                            continue 2;
                        } // Has quote?
                        if (ctype_space ($pstr[$i]) || (strpos ('</>', $pstr[$i]) !== false))
                        {
                            $i++;
                            $value = $key = '';
                            $pstate = dpConstants::PARSE_STATE_PARAM_NAME;
                            continue 2;
                        } // Space?
                        $value .= $pstr[$i];
                        break;

                    case dpConstants::PARSE_STATE_IN_QUOTE:
                        if ($pstr[$i] == $quote_char)
                        {
                            $i++;
                            $params[trim ($key)] = trim ($value);
                            $value = '';
                            $pstate = dpConstants::PARSE_STATE_PARAM_VALUE;
                            continue 2;
                        } // Has quote?
                        $value .= $pstr[$i];
                        break;
                } // switch
                $i++;
            } // while

            if (($value = trim ($value)) && ($key = trim ($key)))
                $params[$key] = $value;
        } // Has parameter string?

        return ($params);
    } // processTagParameters
} // dpPage
?>
