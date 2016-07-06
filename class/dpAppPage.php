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
 
class dpAppPage extends dpPage
{
    private $page_data = false;
    private $url_target_info = false;
    private $_this;
    
    function __construct ($dpPageObject = false)
    {
        $_this = $dpPageObject;
        
        $this->page_data = array ();
        $this->dpURL = $_this->dpURL;
        $this->session = $_this->session;
        $this->url_target_info = $_this->getInfo('page_url_target_info');
    } // __construct
    
    
    public function getPageData ()
    {
        return ($this->page_data);
    } // getData


    public function getValue ($tag = false, $params = false)
    {
        if (trim ($tag) == false)
            return (false);
            
        // Page data has priority
        if (!empty ($this->page_data) && isset ($this->page_data[$tag]))
            return ($this->page_data[$tag]);
        return ($this->callMethod ($tag, true, $params));
    } // getValue
    
    
    public function setValue ($tag = false, $value = false)
    {
        if ((trim ($tag) != false) && is_array ($this->page_data))
            $this->page_data[$tag] = $value;
        return ($value);
    } // setValue


    public function callMethod ($tag, $string = false, $call_params = false)
    {
        $method = dpConstants::DP_PAGE_CLASS_FUNC_PREFIX.$tag;
        if (trim ($tag) != false)
        {
            // Does this method exists on this class?
            if (method_exists ($this, $method))
            {
                $params = array();
                if (is_array ($call_params) && !empty ($call_params))
                    $params[] = $call_params;
                    
                if ($string === true)
                    ob_start();
                
                // Call page object method
                $ret = call_user_func_array (array ($this, $method), $params);
                
                if ($string === true)
                {
                    $out = ob_get_contents();
                    ob_end_clean();
                    return ($out);
                } // Output string?
                return ($ret);
            } else {
                // The method doesn't exist locally... so let's bubble up!
                // Usually we ONLY check for includes
                if ($this->dpURL && ($ue = $this->dpURL->getURLElements ($this->url_target_info)))
                {
                    for ($i = (count ($ue) - 1); $i >= 0; $i--)
                    {
                        // Does this element have an include?
                        if (isset ($ue[$i]['has_include']))
                        {
                            $params = array (
                                'page_path' => $this->dpURL->getPath ($i).'/'.dpConstants::DP_COMMON_INCLUDE,
                                'cache_path' => rtrim ($this->getInfo ('cache_page_path'), '/').$this->dpURL->getURLPath ($i).'/'.dpConstants::DP_COMMON_INCLUDE,
                                'page_class_name' => $this->dpURL->getURLClassName ($i).'_'.dpConstants::DP_COMMON_INCLUDE
                            );

                            // Cache and instantiate
                            if ($dpAppObject = $this->loadPage ($params))
                            {
                                // Check method... Do not blindly call 'callMethod' directly as this
                                // would trigger unnecessary recursion
                                if (method_exists ($dpAppObject, $method))
                                    return ($dpAppObject->callMethod ($tag, $string));
                            } // Instantiate object
                        } // Has includes
                    } // foreach
                } // Do we have a dpURL object?
            } // Do we have the method?
        } // Has tag string?
        return (false);    
    } // callMethod
} // dpAppPage
?>