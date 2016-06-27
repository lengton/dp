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
    private $sid = false;
    private $host_ip = false;
    
    protected $dpURL = false;
    private static $dpTag = false;
    private static $dpTagLen = 0;
    private $template_data = false;
    private $page_object = false;

    private static $register_autoload = false;
    private static $autoload_path = false;
    public $session = false;
    
    
    function __construct ($config = false, $url = false)
    {
        parent::__construct ($config);
        
        // Create URL object
        $this->dpURL = new dpURL ($url);
        
        // Initializations
        self::$dpTag = '<'.dpConstants::DP_TAG.':';
        self::$dpTagLen = strlen (self::$dpTag);
        self::$autoload_path = array ();
    } // __construct
    
    
    public function register_autoload ()
    {
        // REGISTER APP CLASSES AUTOLOAD FUNCTION
        spl_autoload_register (function ($class)
        {
            if (self::$register_autoload === false)
            {
                self::$register_autoload = array();
            } // Initialize app dir cache

            // First level
            $class = str_replace ('\\', '/', ltrim ($class, "/\t\r\r\0\x0B "));
            $class_path =  sprintf ("%s/%s/", $this->getConfig ('dpAppBase'), dpConstants::SCRIPT_DATADIR_CLASS);

            if (file_exists ($class_file = $class_path.$class.dpConstants::DP_PHP_EXTENSION))
            {
                require_once $class_file;
            } else {
                // Go through one more level under this dir
                if (empty (self::$register_autoload))
                {
                    $files = scandir ($class_path);
                    foreach ($files as $file)
                    {
                        if (is_dir ($class_path.$file))
                        {
                            self::$register_autoload[] = $class_path.$file.'/';
                        }
                    }
                } // Cache second level directories
                
                if (!empty (self::$autoload_path) && file_exists (self::$autoload_path[$class]))
                {
                    require_once self::$autoload_path[$class];
                } else {
                    // Search and cache include path
                    foreach (self::$register_autoload as $path)
                    {
                        if (file_exists ($class_file = $path.$class.dpConstants::DP_PHP_EXTENSION))
                        {
                            self::$autoload_path[$class] = $path.$class.dpConstants::DP_PHP_EXTENSION;
                            require_once $class_file;
                        }
                    } // foreach
                } // Is the path cached?
            } // dp App Classes
        }); // spl_autoload_register
    } // register_autoload
    
    
    public function render ()
    {
        $out = '';
        $render_start_time = microtime(true);
        
        // Render only if URL is valid
        if ($this->dpURL && ($this->dpURL->valid === true))
        {
            // REGISTER APP AUTOLOAD FUNCTION
            if (self::$register_autoload === false)
            {
                $this->register_autoload ();
            } // Register dp App autoload functioN?
            
            // Get the URL file target (always a file not a directory)
            $url_target_info = $this->getInfo ('page_url_target_info');
            
            // Start Page Session
            $this->startSession();
            
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
                                        $out .= $this->page_object->callMethod ($value, true);
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
        
        // Display render output
        $this->log (sprintf ('Page[%s] rendered in %.6fus', $this->getConfig('dpScriptName'), (microtime(true) - $render_start_time)));
        echo $out;
    } // render
    
    
    public function startSession ()
    {
        if ($this->session === false)
        {
            // Create dpSession Object
            $this->session = new dpSession ();
        } // Do we have a session object?
    } // startSession
    
    
    public function getInfo ($key)
    {
        $val = false;
        if (strlen ($key))
        {
            switch ($key)
            {
                case 'cache_template_path' :
                    $val = sprintf ("%s/%s/%s/", $this->getConfig ('dpAppBase'), dpConstants::SCRIPT_DATADIR_CACHE, dpConstants::SCRIPT_DATADIR_TEMPLATES);
                    break;
                    
                case 'template_path' :
                    $val = sprintf ("%s/%s/", $this->getConfig ('dpAppBase'), dpConstants::SCRIPT_DATADIR_TEMPLATES);
                    break;
                    
                case 'cache_page_path' :
                    $val = sprintf ("%s/%s/%s/", $this->getConfig ('dpAppBase'), dpConstants::SCRIPT_DATADIR_CACHE, dpConstants::SCRIPT_DATADIR_PAGES);
                    break;                    
                    
                case 'page_path' :
                    $val = sprintf ("%s/%s/", $this->getConfig ('dpAppBase'), dpConstants::SCRIPT_DATADIR_PAGES);
                    break;
                    
                case 'page_url_path' :
                    if ($this->dpURL)
                        $val = $this->dpURL->getURLPath ();
                    break;
                    
                case 'page_fullpath' :
                    if ($this->dpURL)
                        $val = $this->dpURL->getPath ();
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
                        $val = $this->dpURL->getURLClassName ();
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
            if ($this->createDirs ($cached_tp, $file_path = true) === false)
                return (false);
            
            // Read and process template
            $this->template_data = $this->parseTemplate($tp_name, $is_file = true);
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
                        $tagname = '';
                        $line_len = strlen ($line);
                        while ($i < $line_len)
                        {
                            switch ($pstate)
                            {
                                case dpConstants::PARSE_STATE_TAGNAME:
                                    if (ctype_space ($line[$i]) || (strpos ('</>', $line[$i]) !== false))
                                    {
                                        $pstate = dpConstants::PARSE_STATE_ENDTAG;
                                        continue 2;
                                    } // End of tag name?
                                    $tagname .= $line[$i];
                                    break;
                                    
                                case dpConstants::PARSE_STATE_ENDTAG:
                                    if (strlen ($tagname))
                                        $template_data[] = array ('tag' => $tagname);
                                    $tagname = '';
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
            if ($this->createDirs ($cached_pg, $file_path = true) === false)
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
        if ($php_path && file_exists (dpConstants::PHP_COMMANDLINE_PATH))
        {
            $output = array();
            $exec_str = dpConstants::PHP_COMMANDLINE_PATH.' -l '.$php_path;
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
                $tag_params = '';
                $method_type = 'public function ';
                fwrite ($fp, dpConstants::DP_PAGE_CLASS_INDENT.$method_type.dpConstants::DP_PAGE_CLASS_FUNC_PREFIX.$tag.' ('.$tag_params.")\n");
                fwrite ($fp, dpConstants::DP_PAGE_CLASS_INDENT."{\n");
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
                                                    fwrite ($fp, 'echo "'.addslashes ($pdata).'";'.PHP_EOL);
                                                    break;
                                                    
                                                case 'tag' :
                                                    fwrite ($fp, 'echo $this->getValue (\''.addslashes ($pdata).'\');'.PHP_EOL);
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
} // dpPage
?>