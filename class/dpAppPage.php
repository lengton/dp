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

abstract class dpAppPage extends dpPage
{
    private $page_data = false;
    private $url_target_info = false;
    private static $_this;

    function __construct ($dpPageObject = false)
    {
        self::$_this = $dpPageObject;

        $this->page_data = array ();
        $this->dpURL = self::$_this->dpURL;
        $this->session = self::$_this->session;
        $this->url_target_info = self::$_this->getInfo('page_url_target_info');

        // Initialize page_data container
        $this->page_data['appname'] = $this->getConfig ('dpScriptName');
    } // __construct


    public function getPageProp ($name = false)
    {
        $tname = trim ($name);
        if ($tname && property_exists ($this, ($prop = dpConstants::DP_PAGE_CLASS_PROP_PREFIX.$tname)))
            return $this->$prop;

        return NULL;
    } // getPageProp


    public function getPageData ()
    {
        return $this->page_data;
    } // getData


    public function getValue ($tag = false, $params = false, $string = true)
    {
        if (trim ($tag) == false)
            return false;

        // Page data has priority
        if (!empty ($this->page_data) && isset ($this->page_data[$tag]))
            return $this->applyTransformation ($this->page_data[$tag], $params);
        return $this->callMethod ($tag, $string, $params);
    } // getValue


    public function setValue ($tag = false, $value = false)
    {
        if ((trim ($tag) != false) && is_array ($this->page_data))
            $this->page_data[$tag] = $value;
    } // setValue


    public function unsetValue ($tag = false)
    {
        if (is_array ($this->page_data))
            unset ($this->page_data[$tag]);
    } // unsetValue


    public function getParamValue ($key = false, $default = false)
    {
        $caller = next (debug_backtrace())['function'];
        if (($key = trim ($key)) && ($params = $this->getValue ($caller.dpConstants::DP_PAGE_CLASS_FUNC_PARAMS_SUFFIX)))
        {
            if (isset ($params[0]) && is_array ($func_params = $params[0]))
            {
                if (isset ($func_params[$key]) && ($value = $func_params[$key]))
                {
                    if ($value[0] == '@')
                        $value = trim (self::$_this->accessObject ($value));
                    return $value;
                }
                else
                {
                    if ($value = trim (self::$_this->accessObject ($key)))
                        return $value;
                }
            } // has parameters
        } // has function parameters?

        return $default;
    } // getParamValue


    public function getCallingPage ()
    {
        return self::$_this;
    } // getCallingPage


    public function accessObject ($tag = false, $params = false)
    {
        if ($tag[0] != '@')
            return '';

        $object_label = substr ($tag, 1);
        $label_items = explode ('.', $object_label);
        if (!empty ($label_items) && ($value = $this->getValue ($label_items[0], false, false)))
        {
            switch (gettype ($value))
            {
                case 'boolean' :
                case 'integer' :
                case 'double' :
                case 'string' :
                    break;

                case 'array' :
                    if (($item_count = count ($label_items)) > 1)
                    {
                        $base_val = $value;
                        for ($i = 1; $i < $item_count; $i++)
                        {
                            if (is_array ($base_val) && (isset ($base_val[$label_items[$i]]) || array_key_exists ($label_items[$i], $base_val)))
                                $base_val = $base_val[$label_items[$i]];
                            else break;
                        } // for

                        if ($base_val != $value)
                        {
                            $value = $base_val;
                            break;
                        } // Not an array?
                    } // has reference items?
                    $value = implode (', ', $value);
                    break;

                case 'object' :
                case 'resource' :

                case 'NULL' :
                case 'unknown type' :
                default :
            } // switch
        } // has value?

        if (!empty ($params))
            $value = $this->applyTransformation ($value, $params);

        return ' '.$value;
    } // accessObject


    public function applyTransformation ($value = false, $params = false)
    {
        if ($params && is_array ($params) && !empty ($params))
        {
            // Do we need to change formatting of values?
            if (isset ($params['format']))
            {
                switch ($params['format'])
                {
                    case 'dollar' :
                        $decimals = 2;
                        if (isset ($params['decimals']))
                        {
                            $decnum = intVal ($params['decimals']);
                            $decimals = ($decnum > 0 ? $decnum : 2);
                        } // decimals specified?

                        $actual_value = $value;
                        $value = '';
                        if ($actual_value < 0.00)
                            $value .= '(';
                        $value .= '$'.number_format ((double) abs ($actual_value), $decimals);
                        if ($actual_value < 0.00)
                            $value .= ')';
                        break;

                    case 'date' :
                        $formatstr = 'r';
                        if (isset ($params['formatstr']))
                            $formatstr = $params['formatstr'];
                        $value = date ($formatstr, strtotime ($value));
                        break;

                    case 'custom' :
                        $formatstr = '%s';
                        if (isset ($params['formatstr']))
                            $formatstr = $params['formatstr'];
                        $value = sprintf ($formatstr, $value);
                        break;

                } // switch
            } // Do we need to transform value?

            // Do we need to call a function?
            // Note: This assigns $value the 'return'ed value from the call. (not 'echo')
            if (isset ($params['call']))
            {
                $value = $this->callMethod ($params['call'], false, array ('value' => $value));
            } // call a function?
        } // has parameters?

        return $value;
    } // applyTransformation


    public function callMethod ($tag, $string = false, $call_params = false)
    {
        // Check if tag passed is an array
        if (is_array ($tag) && isset ($tag['name']))
        {
            if (isset ($tag['params']))
                $call_params = $tag['params'];
            $tag = $tag['name'];  // Overrites tag
        } // an array?
        if (trim ($tag) != false)
        {
            // Does this method exists on this class?
            $method = dpConstants::DP_PAGE_CLASS_FUNC_PREFIX.$tag;
            if (method_exists ($this, $method))
            {
                if ($string === true)
                    ob_start();

                $func_call_params = array ();
                if ($call_params && !empty ($call_params))
                    $func_call_params[] = $call_params;

                // Call page object method
                $ret = call_user_func_array (array ($this, $method), $func_call_params);

                if ($string === true)
                {
                    $out = ob_get_contents();
                    ob_end_clean();
                    return $out;
                } // Output string?

                return $ret;
            }
            else
            {
                // The method doesn't exist locally... so let's bubble up!
                // Usually we ONLY check for includes
                if ($this->dpURL && ($ue = $this->dpURL->getURLElements ($this->url_target_info)))
                {
                    for ($i = (count ($ue) - 1); $i >= 0; $i--)
                    {
                        // Does this element have an include?
                        if (isset ($ue[$i]['has_include']))
                        {
                            $params = array
                            (
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
                                {
                                    // Pass the calling object -- always at the first location
                                    return $dpAppObject->callMethod ($tag, $string, $call_params);
                                } // method exists?
                            } // Instantiate object
                        } // Has includes
                    } // foreach
                } // Do we have a dpURL object?
            } // Do we have the method?
        } // Has tag string?

        return false;
    } // callMethod
} // dpAppPage
?>
