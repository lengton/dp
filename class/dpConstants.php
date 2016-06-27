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
 
class dpConstants
{
    const TAGNAME_DEFAULT = 'dpDefault';
    const TAGNAME_STARTPAGE = 'dpStart';
    const TAGNAME_ENDPAGE = 'dpEnd';
    
    const URL_TRIM_STR = "/\t\r\r\0\x0B ";

    const PHP_COMMANDLINE_PATH = '/usr/local/bin/php';
    const PHP_TAG_START = '<?php';
    const PHP_TAG_END = '?>';
    
    const DP_DIR = '/dp';
    const DP_TAG = 'dp';
    const DP_SWITCH_RAWDIR = '.raw';
    const DP_DATA_OPERATOR_PREFIX = 'opr_';
    const DP_DATA_CONJUNCTION_PREFIX = 'conj_';
    
    const DP_COMMON_INCLUDE = 'dpInclude';
    
    const DP_PAGE_CLASS_HEADER = '// dp Page Class File';
    const DP_PAGE_CLASS_PREFIX = 'dpAppPage_';
    const DP_PAGE_CLASS_FUNC_PREFIX = 'dpAppPageFunc_';
    const DP_PAGE_CLASS_INDENT = '    ';
    const DP_PHP_EXTENSION = '.php';
    
    const SQL_METHOD_PREFIX = 'dpSQL_';
    
    const SCRIPT_DATADIR_SUFFIX = '_dpApp';
    const SCRIPT_DATADIR_PAGES = 'pages';
    const SCRIPT_DATADIR_TEMPLATES = 'templates';
    const SCRIPT_DATADIR_CACHE = 'cache';
    const SCRIPT_DATADIR_CLASS = 'class';
    const SCRIPT_DATADIR_BIN = 'bin';
    const SCRIPT_DATADIR_LOG = 'log';
    const SCRIPT_DATADIR_APPBIN = 'appbin';
    
    const PARSE_STATE_TAGLINE = 0;    
    const PARSE_STATE_STATIC_TEXT = 1;
    const PARSE_STATE_FINDTAGNAME = 2;    
    const PARSE_STATE_TAGNAME = 3;
    const PARSE_STATE_TAGBODY = 4;
    const PARSE_STATE_ENDTAG = 5;
    const PARSE_STATE_PHPCODE = 6;
    const PARSE_STATE_IN_QUOTE = 7;
    
    const DB_UPDATE_OR_INSERT = 'dpUpdateOrInsert';
    
} // dpConstants
?>