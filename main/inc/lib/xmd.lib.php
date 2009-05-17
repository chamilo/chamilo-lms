<?php /*                                                    <!-- xmd.lib.php -->
                                                <!-- XML MiniDom, 2006/12/13 -->

<!-- Copyright (C) 2005 rene.haentjens@UGent.be - see note at end of text    -->
<!-- Released under the GNU GPL V2, see http://www.gnu.org/licenses/gpl.html -->

*/

/**
============================================================================== 
*	This is the XML Dom library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@author Rene Haentjens
*	@package dokeos.library
============================================================================== 
*/

class xmddoc
{
 /* This MiniDom for XML essentially implements an array of elements, each
    with a parent, a name, a namespace-URI, attributes & namespace definitions,
    and children. Child nodes are a mix of texts and subelements. Parent 
    and subelements are stored as elementnumbers, the root is element 0.
    
    Parsing is built on James Clark's expat, by default enabled in PHP.
    
    The MiniDom is an alternative to the experimental DOM XML functions. 
    It is open source PHP and requires no extra libraries.

    Restrictions of the MiniDom:
    - no two attributes with same name (different namespaces) on one element;
    - only 'ISO-8859-1' right now; author will investigate 'UTF-8' later;
    - processing instructions & external entities are ignored;
    - no distinction between text and cdata child nodes;
    - xmd_xml(nonrootelement) may not generate all needed namespace definitions;
    - xmd_value, xmd_html_value, xmd_select_xxx, xmd_update, xmd_update_many:
      path parameter uses names without namespaces 
      and supports only a small subset of XPath, with some extensions;
    - maximum 11 auto-generated namespace prefixes (can be changed in xmddoc)

    Namespace definitions are stored as attributes, with name = 'xmlns...'
    e.g. xmlns:xml='http://www.w3.org/XML/1998/namespace'
    e.g. xmlns='http://www.imsglobal.org/xsd/imscp_v1p1' (default namespace)
    
    Exposed methods:
    
    new xmddoc(array_of_strings, charset = 'ISO-8859-1'): parse strings
    new xmddoc(names, numbers, textstring): restore from cached arrays & string
    
    xmd_add_element(complete_name, parent, attributes_with_complete_names)
        complete name = [ URI + ':' + ] name
    xmd_set_attribute(element, complete_attribute_name, value) (id. as above)
    xmd_add_text(text, element)
    xmd_add_text_element(complete_name, text, parent, attributes) =
        xmd_add_text(text, xmd_add_element(complete_name, parent, attributes))
    
    xmd_get_element(element) => children, attributes, '?name', '?parent'
    xmd_get_ns_uri(element [, attribute_name_without_uri] )
    
    xmd_text(element): combines element and subelements' text nodes
    xmd_xml(): generate XML-formatted string (reverse parsing)
    xmd_xml(indent_increase, initial_indent, lbr): e.g. '  ', '', "\n"
    
    xmd_value(path): follow path from root, return attribute value or text
        e.g. 'manifest/organizations/@default' 'body/p[1]' (1=first, -1=last)
    xmd_value(path, parent, fix, function): find value(s) with path from parent,
        apply function and decorate with fix = ('pre'=>..., 'in'=>..., 'post')
        e.g. 'general/title/*' array('in' => ', ')
    extensions to XPath:
        -  and  +  for previous and next sibling, e.g. general/title/+/string
        -name and +name for sibling with specific name, e.g. item[2]/+item
        .. for parent, e.g. general/title/../../technical/format (stops at root)
        @* for location (element number within siblings, starts at 1)
        @*name for location in siblings with specific name
        @. for element name, e.g. organization/*[1]/@.
    namespaces are not supported in paths: they use names without URI
    
    xmd_html_value(pathFix, parent, fun): 'path' 'path infix' 'prefix -% path'
        'path infix %- postfix': fun = 'htmlspecialchars' by default
    
    xmd_select_elements(path, parent): find element nodes with path (see above)
    xmd_select_single_element (id.) returns -1 or elementnumber
    xmd_select_elements_where(path, subpath, value, parent): e.g. '@id', '12'
        is like XPath with path[@id='12']; subpath = '.' means text
    xmd_select_elements_where_notempty(path, subpath, parent): e.g. '@id'
    xmd_select_xxx methods only select elements, not attributes
    
    xmd_remove_element(childelement_number)
    xmd_remove_nodes(childelement_numbers_and_strings, parent)
    
    xmd_update(path, text, parent): select single element, then:
        text element:     replace text by new text
        attribute:        give attribute new value = text
        somepath/!newtag: create new child element containing text
        somepath/~:       delete single (first or only) element
    xmd_update_many(paths, subpath, ...): paths can be path1,path2,...:
        for all elements selected by all paths, update with subpath
    
    xmd_copy_foreign_child(fdoc, child, parent):
        copies fdoc's child as a new child of parent;
        note this method hasn't been tested for all cases (namespaces...)
    
    xmd_cache(): dump xmddoc into names+numbers+textstring for serialization
    
    Order of parameters (if present) for xmd_xxx methods:
        name, text, children, path, subPath, value, 
            parent, fix, fun, attributes (name value)
    
    
    Properties: (G)lobal to xmddoc or array (one for each xmddoc (E)lement)
    
    e.g. $this->name[0] is the name of the document root element
    e.g. $this->names[$this->ns[0]] is its namespace URI
    e.g. $this->attributes[0]['title'] is the value of its attribute 'title'
    e.g. $this->attributes[0]['xmlns:a'] is the URI for prefix 'a:'
 */
    
    var $names;         //G array: n => namespace URI (0 => '')
    var $numbers;       //G array: numeric dump of xmddoc for caching
    var $textstring;    //G string: string dump of xmddoc for caching
    var $error;         //G string: empty or parsing error message
    var $_nesting;      //G array: nested elements while parsing (internal)
    var $_ns;           //G array: namespace defs for upcoming element (id.)
    var $_concat;       //G bool: concatenate cData with previous (id.)
    var $_nsp;          //G array: namespace prefixes in use somewhere (id.)
    var $_last;         //G int: last used elementnumber (id.)
    var $_strings;      //G int: number of string child nodes cached (id.)
    
    var $parent;        //E int: elementnumber: 0 is root, -1 is parent of root
    var $name;          //E string: element name, without namespace
    var $ns;            //E int: index into $names to find namespace URI
    var $attributes;    //E array: attribute name(without namespace) => value
    var $atns;          //E array: attribute name(id.) => index into $names
    var $children;      //E array: elementnumbers and strings (text children)
    
    
    function xmd_get_element($parent = 0)  // for convenience, readonly copy
    {
        // returns mixed array: children + texts have numeric key,
        // other elements are attributes, '?name' and '?parent'
        
        if ($parent < 0 || $parent > $this->_last) return array();
        
        return array_merge($this->children[$parent], $this->attributes[$parent], 
            array('?name' => $this->name[$parent],
                '?parent' => $this->parent[$parent]));
    }
    
    
    function xmd_get_ns_uri($parent = 0, $attName = '')
    {
        if ($parent < 0 || $parent > $this->_last) return '';
        
        return $attName ? $this->names[$this->atns[$parent][$attName]] : 
            $this->names[$this->ns[$parent]];
    }
    
    
    function xmd_remove_element($child)  // success = TRUE
    {
        if ($child <= 0 || $child > $this->_last) return FALSE;
        
        $parent = $this->parent[$child];
        
        foreach ($this->children[$parent] as $key => $value)
            if ($value === $child)
            {
                unset($this->children[$parent][$key]); return TRUE;
            }
        
        return FALSE;
    }
    
    
    function xmd_remove_nodes($children, $parent = 0)  // success = TRUE
    {
        if ($parent < 0 || $parent > $this->_last) return FALSE;
        
        if (!is_array($children)) $children = array($children);
        
        foreach ($children as $child)
        {
            $childFound = FALSE;
            foreach ($this->children[$parent] as $key => $value)
                if ($value === $child)
                {
                    unset($this->children[$parent][$key]);
                    $childFound = TRUE; break;
                }
            if (!$childFound) return FALSE;
        }
        
        return TRUE;
    }
    
    
    function xmd_update($xmPath, $text = '', $parent = 0)  // success = TRUE
    {
        if ($parent < 0 || $parent > $this->_last || 
                !is_string($text) || !is_string($xmPath)) return FALSE;
        
        $m = array();
        if (api_ereg('^(.*)([~!@])(.*)$', $xmPath, $m))  // split on ~ or ! or @
        {
            $xmPath = $m[1]; $op = $m[2]; $name = $m[3];
        }
        
        if (($elem = $this->xmd_select_single_element($xmPath, $parent)) == -1) 
            return FALSE;
    
        if (isset($op))
        {
            if     ($op == '!' && $name)
            {
                $this->xmd_add_text_element($name, $text, $elem); return TRUE;
            }
            elseif ($op == '@' && $name)
            {
                $this->attributes[$elem][$name] = $text; return TRUE;
            }
            elseif ($op == '~' && !$name)
                return $this->xmd_remove_element($elem);
        
            return FALSE;
        }
        
        if (($nch = count($this->children[$elem])) > 1) return FALSE;
        
        $this->children[$elem][0] = $text; return TRUE;
    }
    
    
    function xmd_update_many($xmPaths, $subPath = '', $text = '', $parent = 0)
    {
        $result = TRUE;
        
        foreach (explode(',', $xmPaths) as $xmPath)
        foreach ($this->xmd_select_elements($xmPath, $parent) as $elem)
            $result &= $this->xmd_update($subPath, $text, $elem);
            // '&=' always evaluates rhs, '&&=' skips it if $result is FALSE
        
        return $result;
    }

    
    function xmd_copy_foreign_child($fdoc, $fchild = 0, $parent = 0)
    {
        $my_queue = array($fchild, $parent);  // optimization, see below
        
        while (!is_null($fchild = array_shift($my_queue)))
        {
            $parent = array_shift($my_queue);
            
            if (is_string($fchild)) 
                $this->xmd_add_text($fchild, $parent);
            
            elseif (isset($fdoc->name[$fchild]))
            {
                $fullname = $fdoc->name[$fchild];
                $attribs = array(); $nsdefs = array();
                
                if (($nsn = $fdoc->ns[$fchild])) 
                    $fullname = $fdoc->names[$nsn] . ':' . $fullname;
                
                foreach ($fdoc->attributes[$fchild] as $name => $value)
                {
                    if (($p = strrpos($name, ':')) !== FALSE)  // 'xmlns:...'
                        $nsdefs[$name] = $value;
                    
                    else
                    {
                        if (($nsn = $fdoc->atns[$fchild][$name])) 
                            $name = $fdoc->names[$nsn] . ':' . $name;
                        $attribs[$name] = $value;
                    }
                }
                
                $child = $this->xmd_add_element($fullname, $parent, 
                    array_merge($attribs, $nsdefs));
                
                foreach ($fdoc->children[$fchild] as $ch) 
                    array_push($my_queue, $ch, $child);
                // recursive call was 10 times slower...
            }
        }
    }
    
    
    function xmd_add_element($name, $parent = 0, $attribs = array())
    { 
        if (!is_string($name) || $name == '') return -1;
        
        if (($p = strrpos($name, ':')) !== FALSE)  // URI + ':' + name
            if ($p == 0 || $p == api_strlen($name) - 1) return -1;
        
        $child = ($this->_last += 1); $uris = array(); $uri = '';
        
        if ($p)
        {
            $uri = api_substr($name, 0, $p); $name = api_substr($name, $p + 1);
            $uris[] = $uri;  // check uris after defining all attributes
        }
        
        $this->parent[$child] = $parent; $this->name[$child] = $name;
        $this->ns[$child] = $uri ? $this->_lookup($uri) : 0;
        $this->children[$child] = array();
        
        $this->attributes[$child] = array(); $this->atns[$child] = array();
        
        foreach ($attribs as $name => $value)
            if (($uri = $this->xmd_set_attribute($child, $name, $value, FALSE)))
                $uris[] = $uri;  // check at end, not immediately
        
        if ($parent >= 0 && $parent <= $this->_last) 
            $this->children[$parent][] = $child;  // link to parent
        
        foreach ($uris as $uri) $this->_nsPfx($child, $uri);
        // find prefix (child and upwards) or create new prefix at root
        
        return $child;
    }
    
    
    function xmd_set_attribute($parent, $name, $value, $checkurihaspfx = TRUE)
    {
        if (!is_string($name) || $name == '') return '';
        
        if (($p = strrpos($name, ':')) !== FALSE)  // URI + ':' + name
            if ($p == 0 || $p == api_strlen($name) - 1) return '';
        
        $uri = '';  // beware of 'xmlns...', which is a namespace def!
        
        if ($p) if (api_substr($name, 0, 6) != 'xmlns:')
        {
            $uri = api_substr($name, 0, $p); $name = api_substr($name, $p + 1);
        }
        $this->attributes[$parent][$name] = $value;
        $this->atns[$parent][$name] = $uri ? $this->_lookup($uri) : 0;
        if ($checkurihaspfx) if ($uri) $this->_nsPfx($parent, $uri);
        
        if (api_substr($name, 0, 6) == 'xmlns:')  // namespace def with prefix
            $this->_nsp[api_substr($name, 6)] = $value;  // prefix is in use
        
        return $uri;
    }
    
    
    function xmd_add_text($text, $parent = 0)  // success = TRUE
    {
        if ($parent < 0 || $parent > $this->_last || !is_string($text))
            return FALSE;
    
        if ($text) $this->children[$parent][] = $text; return TRUE;
    }
    
    
    function xmd_add_text_element($name, $text, $parent = 0, $attribs = array())
    {
        $this->xmd_add_text($text, 
            $child = $this->xmd_add_element($name, $parent, $attribs));
    
        return $child;
    }
    
    
    function xmd_text($parent = 0)
    {
        if ($parent < 0 || $parent > $this->_last) return '';
        
        $text = '';  // assemble text subnodes and text in child elements
    
        foreach ($this->children[$parent] as $child)
            $text .= is_string($child) ? $child : $this->xmd_text($child);
        
        return $text;
    }
    
    
    function xmd_xml($increase = '  ', $indent = '', $lbr = "\n", $parent = 0)
    {
		global $charset;

        if ($parent < 0 || $parent > $this->_last) return '';
        
        $uri = $this->names[$this->ns[$parent]];
        $pfxc = ($uri == '') ? '' : $this->_nsPfx($parent, $uri);
        
        $dbg = '';  // ($uri == '') ? '' : (' <!-- ' . $uri . ' -->');
        
        $result = $indent . '<' . ($element = $pfxc . $this->name[$parent]);
        
        $atnsp = $this->atns[$parent];
        
        foreach ($this->attributes[$parent] as $name => $value)
        {
            if (isset($atnsp[$name]))
            	$atnsn = $atnsp[$name];
            elseif (isset($atnsn))
            	unset($atnsn);
            $uri = isset($atnsn) && isset($this->names[$atnsn]) ? 
                $this->names[$atnsn] : '';
            $pfxc = ($uri == '') ? '' : $this->_nsPfx($parent, $uri);
            $result .= ' ' . $pfxc . $name 
                . '="' . htmlspecialchars($value, ENT_QUOTES, $charset) . '"';
        }
        
        if (count($this->children[$parent]) == 0)
            return $result . ' />' . $dbg;
        
        $result .= '>';
        
        foreach ($this->children[$parent] as $child)
            $result .= is_string($child) ? htmlspecialchars($child, ENT_QUOTES, $charset) : ($lbr . 
                $this->xmd_xml($increase, $indent.$increase, $lbr, $child));
        
        if (!is_string($child)) $result .= $lbr . $indent;  // last $child
        
        return $result . '</' . $element . '>' . $dbg; 
    }
    
    
    function xmd_value($xmPath, $parent = 0, $fix = array(), $fun = '')
    {
        // extensions:  @*[name] for element position (starts at 1)
        //              @. for element (tag)name
        
        if ($parent < 0 || $parent > $this->_last || !is_string($xmPath)) 
            return '';
        
        if (($p = strrpos($xmPath, '@')) !== FALSE)
        {
            $attName = api_substr($xmPath, $p+1); $xmPath = api_substr($xmPath, 0, $p);
        }
        
        if (!($elems = $this->xmd_select_elements($xmPath, $parent))) return '';
        
        $result = ''; $fixin = isset($fix['in']) ? $fix['in'] : '';
        
        foreach ($elems as $elem)
        {
            $value = isset($attName) && api_strlen($attName) >= 1 ?
                ($attName == '.' ? $this->name[$elem] : 
                    ($attName{0} == '*' ? 
                        $this->_sibnum($elem, api_substr($attName, 1)) :
                        $this->attributes[$elem][$attName])) : 
                $this->xmd_text($elem);
            $result .= $fixin . ($fun ? $fun($value) : $value);
        }
        
        return  (isset($fix['pre']) ? $fix['pre'] : '') . 
                api_substr($result, api_strlen($fixin)) . 
                (isset($fix['post']) ? $fix['post'] : '');
    }
    
    
    function xmd_html_value($xmPath, $parent = 0, $fun = 'htmlspecialchars')
    {
        if (!is_string($xmPath)) return '';
        
        $fix = array();
        
        if (($p = api_strpos($xmPath, ' -% ')) !== FALSE)
        {
            $fix['pre'] = api_substr($xmPath, 0, $p);
            $xmPath = api_substr($xmPath, $p+4);
        }
        if (($p = api_strpos($xmPath, ' %- ')) !== FALSE)
        {
            $fix['post'] = api_substr($xmPath, $p+4);
            $xmPath = api_substr($xmPath, 0, $p);
        }
        if (($p = api_strpos($xmPath, ' ')) !== FALSE)
        {
            $fix['in'] = api_substr($xmPath, $p+1);
            $xmPath = api_substr($xmPath, 0, $p);
        }
        
        return $this->xmd_value($xmPath, $parent, $fix, $fun);
    }
    
    
    function xmd_select_single_element($xmPath, $parent = 0)  // for convenience
    {
        $elements = $this->xmd_select_elements($xmPath, $parent);
        if (count($elements) == 0) return -1;
        return $elements[0];
    }
    
    
    function xmd_select_elements_where($xmPath, 
            $subPath = '.', $value = '', $parent = 0)
    {
        if (!is_string($subPath)) return array();
        
        $elems = array(); if ($subPath == '.') $subPath = '';
        
        foreach ($this->xmd_select_elements($xmPath, $parent) as $elem)
            if ($this->xmd_value($subPath, $elem) == $value) $elems[] = $elem;
        
        return $elems;
    }
    
    
    function xmd_select_elements_where_notempty($xmPath, 
            $subPath = '.', $parent = 0)
    {
        if (!is_string($subPath)) return array();
        
        $elems = array(); if ($subPath == '.') $subPath = '';
        
        foreach ($this->xmd_select_elements($xmPath, $parent) as $elem)
            if ($this->xmd_value($subPath, $elem)) $elems[] = $elem;
        
        return $elems;
    }
    
    
    function xmd_select_elements($xmPath, $parent = 0)
    {
        // XPath subset:    e1/e2/.../en, also * and e[n] and *[n] (at 1 or -1)
        //                  /*/... starts from root, regardless of $parent
        // extensions:      e= - or + (previous & next sibling)
        //                  e= -name or +name (sibling of specific name)
        //                  e= .. (stops at root, so too many doesn't matter)
        
        if (api_substr($xmPath, 0, 3) == '/*/')
        {
            $xmPath = api_substr($xmPath, 3); $parent = 0;
        }
        
        if ($parent < 0 || $parent > $this->_last) return array();
        
        while (api_substr($xmPath, 0, 1) == '/') $xmPath = api_substr($xmPath, 1);
        while (api_substr($xmPath, -1) == '/')   $xmPath = api_substr($xmPath, 0, -1);
        
        if ($xmPath == '' || $xmPath == '.') return array($parent);
        
        if ($xmPath == '..')
        {
            if ($parent > 0) return array($this->parent[$parent]);
            return array($parent);
        }
        
        if ($xmPath{0} == '-' || $xmPath{0} == '+')
        {
            $sib = $this->_sibnum($parent, api_substr($xmPath, 1), $xmPath{0});
            if ($sib == -1) return array(); return array($sib);
        }
        
        $m = array();
        if (api_ereg('^(.+)/([^/]+)$', $xmPath, $m))  // split on last /
        {
            if (!($set = $this->xmd_select_elements($m[1], $parent))) 
                return $set;  // which is empty array
            if (count($set) == 1) 
                return $this->xmd_select_elements($m[2], $set[0]);
            
            $bigset = array(); $m2 = $m[2];
            foreach ($set as $e)
                $bigset = array_merge($bigset, 
                    $this->xmd_select_elements($m2, $e));
            return $bigset;
        }
        
        $xmName = $xmPath; $xmNum = 0; $elems = array();
        
        if (api_ereg('^(.+)\[(-?[0-9]+)\]$', $xmPath, $m))
        {
            $xmName = $m[1]; $xmNum = (int) $m[2];
        }
        
        foreach ($this->children[$parent] as $child) if (!is_string($child))
            if ($xmName == '*' || ($this->name[$child]) == $xmName)
                $elems[] = $child;
        
        if ($xmNum == 0) return $elems;
        
        $xmNum = ($xmNum > 0) ? $xmNum - 1 : count($elems) + $xmNum;
        
        return ($xmNum < count($elems)) ? array($elems[$xmNum]) : array();
    }

    // Notes on parsing and caching:
    // - parsing 388 KB -> 0.94 sec
    // - caching 298 KB <- 1.63 sec: 11387 elements, 5137 string nodes
    // - uncache 298 KB -> 0.42 sec
    // - $this->children[$n][] in a loop is quicker than a temporary array
    //   $children[] and copying $this->children[$n] = $children after the loop
    // - incremental operator ++$numptr is not quicker than ($numptr += 1)
    // - numbers & textstring: more compact with base64_encode(gzcompress()) 
        
    function xmd_cache()  // store all data in numbers+names+textstring
    {
        $this->numbers = array(); $this->textstring = ''; $this->_strings = 0;
        // add all element- and attributenames to names - see below
        
        for ($n = 0; $n <= $this->_last; $n++)
        {
            $this->numbers[] = count($this->children[$n]);
            
            foreach ($this->children[$n] as $ch)
            {
                if (is_string($ch))
                {
                    $this->numbers[] = 0; $this->_strings += 1;
                    $this->numbers[] = strlen($ch); $this->textstring .= $ch; //!!! Here strlen() has not been changed to api_strlen(). To be investigated. Ivan Tcholakov, 29-AUG-2008.
                }
                else
                {
                    $this->numbers[] = ($ch-$n);  // more compact than $ch
                }
            }
            
            $this->numbers[] = count($this->attributes[$n]);
            
            foreach ($this->attributes[$n] as $name => $value)
            {
                $this->numbers[] = $this->_lookup($name);
                $this->numbers[] = $this->atns[$n][$name];
                $this->numbers[] = strlen($value); $this->textstring .= $value; //!!! Here strlen() has not been changed to api_strlen(). To be investigated. Ivan Tcholakov, 29-AUG-2008.
            }
            
            $this->numbers[] = $this->_lookup($this->name[$n]);
            $this->numbers[] = $this->ns[$n];
            $this->numbers[] = $n - $this->parent[$n];  // more compact
        }
    }
    

    function xmddoc($strings, $charset = null, $textstring = '')
    {
    	if (empty($charset))
    	{
    		$charset = api_get_system_encoding();
    	}

        $this->parent = array();      $this->name = array();
        $this->ns = array();          $this->attributes = array();
        $this->atns = array();        $this->children = array();
        $this->error = '';            $this->_nesting = array();
        $this->_ns = array();         $this->_last = -1;
        
        $this->_nsp = array();
        foreach (explode(',', 'eb,tn,eg,ut,as,ne,jt,ne,ah,en,er') as $pfx)
            $this->_nsp[$pfx] = '';
        
        if (is_array($charset))  // new xmddoc($names, $numbers, $textstring)
        {
            $this->names = $strings;         $this->numbers = $charset;
            $this->textstring = $textstring; $this->_uncache(); return;
        }
        
        $this->names = array(); $this->_lookup('');  // empty ns is number 0
        
        // This is a quick workaround.
        // The xml-parser supports only ISO-8859-1, UTF-8 and US-ASCII.
    	// See http://php.net/manual/en/function.xml-parser-create-ns.php
        //$xml_parser = xml_parser_create_ns($charset, ':');
        $xml_parser = xml_parser_create_ns(api_is_utf8($charset) ? 'UTF-8' : 'ISO-8859-1', ':');

        xml_set_object($xml_parser,$this);  // instead of ...,&$this
        // See PHP manual: Passing by Reference vs. xml_set_object
        xml_set_element_handler($xml_parser, '_startElement', '_endElement');
        xml_set_character_data_handler($xml_parser, '_cData');
        xml_set_start_namespace_decl_handler($xml_parser, '_startNs');
        // xml_set_end_namespace_decl_handler($xml_parser, '_endNs');
        // xml_set_default_handler ($xml_parser, '');
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, FALSE);
     
        if (!is_array($strings)) $strings = array($strings);
        
        if (count($strings) && (api_substr($strings[0], 0, 5) != '<?xml') &&
            !xml_parse($xml_parser, 
                '<?xml version="1.0" encoding="' . $charset . '"?>', FALSE))
        {
            $this->error = 'Encoding ' . $charset . ': ' . 
                xml_error_string(xml_get_error_code($xml_parser));
            $strings = array();
        }
        
        foreach ($strings as $s)
        {
            if (api_substr($s, -1) != "\n") $s .= "\n";
            
            if (!xml_parse($xml_parser, $s, FALSE))
            {
                $errCode = xml_get_error_code($xml_parser);
                $this->error = 'Line '. xml_get_current_line_number($xml_parser) .
                    ' (c'  . xml_get_current_column_number($xml_parser) .
                    // ', b'  . xml_get_current_byte_index($xml_parser) .
                    '): error ' . $errCode . '= ' . xml_error_string($errCode);
                break;  // the error string is English...
            }
        }
        
        xml_parse($xml_parser, '', TRUE); xml_parser_free($xml_parser);
    }
    
    
    // internal methods
    
    function _sibnum($parent, $name = '', $pmn = 'N')  // sibling or number
    {
        if ($parent <= 0) return -1;
        
        $found = FALSE; $prev = -1; $next = -1; $num = 0;
        
        foreach ($this->children[$this->parent[$parent]] as $child)
        {
            if (is_string($child)) continue;
            
            $name_ok = $name ? ($this->name[$child] == $name) : TRUE;
            
            if ($found && $name_ok)
            {
                $next = $child; break;
            }
            elseif ($parent === $child)
            {
                $num ++; $found = TRUE;
            }
            elseif ($name_ok)
            {
                $num ++; $prev = $child;
            }
        }
        
        return ($pmn == '-') ? $prev : (($pmn == '+') ? $next : $num);
    }

    function _uncache()  // restore all data from numbers+names+textstring
    {
        $n = -1; $numptr = -1; $txtptr = 0; $count = count($this->numbers);
        $A0 = array();  // believe it or not, this makes the loops quicker!
        
        while (++$numptr < $count)
        {
            $n++;
            
            if (($countdown = $this->numbers[$numptr]) == 0)
            {
                $this->children[$n] = $A0;
            }
            else while (--$countdown >= 0)
            {
                if (($chc = $this->numbers[++$numptr]) == 0)
                {
                    $this->children[$n][] = api_substr($this->textstring, 
                        $txtptr, ($len = $this->numbers[++$numptr]));
                    $txtptr += $len;
                }
                else
                {
                    $this->children[$n][] = $n + $chc;
                }
            }
            
            if (($countdown = $this->numbers[++$numptr]) == 0)
            {
                $this->attributes[$n] = $this->atns[$n] = $A0;
            }
            else while (--$countdown >= 0)
            {
                $name = $this->names[$this->numbers[++$numptr]];
                $this->atns[$n][$name] = $this->numbers[++$numptr];
                $this->attributes[$n][$name] = api_substr($this->textstring, 
                    $txtptr, ($len = $this->numbers[++$numptr]));
                $txtptr += $len;
            }
            
            $this->name[$n] = $this->names[$this->numbers[++$numptr]];
            $this->ns[$n] = $this->numbers[++$numptr];
            
            $this->parent[$n] = $n - $this->numbers[++$numptr];
        }
        
        $this->_last = $n;
    }

    function _startElement($parser, $name, $attribs)
    { 
        $level = count($this->_nesting);
        $parent = ($level == 0) ? -1 : $this->_nesting[$level-1];
        
        $child = $this->xmd_add_element($name, $parent, 
            array_merge($attribs, $this->_ns));
            
        $this->_nesting[] = $child; $this->_ns = array();
        
        $this->_concat = FALSE;  // see _cData
    }

    function _endElement($parser, $name)
    {
        array_pop($this->_nesting); $this->_concat = FALSE;
    }

    function _cData($parser, $data)
    {
        if (!ltrim($data)) return;  // empty line, or whitespace preceding <tag>
        
        $level = count($this->_nesting);
        $parent = ($level == 0) ? -1 : $this->_nesting[$level-1];
        
        if ($parent >= 0) 
        {
            $nc = count($this->children[$parent]);
            $pcs = ($nc > 0 && is_string($this->children[$parent][$nc - 1]));
            
            if ($pcs && api_strlen($data) == 1) $this->_concat = TRUE;
            // expat parser puts &xx; in a separate cData, try to re-assemble
            
            if ($pcs && $data{0} > '~') $this->_concat = TRUE;
            // PHP5 expat breaks before 8-bit characters
            
            if ($this->_concat)
            {
                $this->children[$parent][$nc - 1] .= $data;
                $this->_concat = (api_strlen($data) == 1);
            }
            else
                $this->children[$parent][] = $pcs ? "\n" . $data : $data;
        }
    }
    
    function _startNs($parser, $pfx, $uri)  // called before _startElement
    {
        $this->_ns['xmlns' . ($pfx ? ':'.$pfx : '')] = $uri;
        $this->_nsp[$pfx] = $uri;
    }
    
    function _nsPfx($ppar, $uri)  // find namespace prefix
    {
        while ($ppar >= 0)
        {
            foreach ($this->attributes[$ppar] as $name => $value)
                if (api_substr($name, 0, 5) == 'xmlns' && $value == $uri)
                {
                    $pfxc = api_substr($name, 6) . api_substr($name, 5, 1); break 2;
                }
                
            $ppar = $this->parent[$ppar];
        }
        
        if ($ppar >= 0) return $pfxc; if ($uri == '') return '';
        
        if ($uri == 'http://www.w3.org/XML/1998/namespace') return 'xml:';
        
        foreach($this->_nsp as $pfx => $used) if (!$used) break;
        
        $this->_nsp[$pfx] = $uri; $xmlnspfx = 'xmlns:' . $pfx;
        $this->attributes[0][$xmlnspfx] = $uri; $this->atns[0][$xmlnspfx] = 0;
        
        return $pfx . ':';
        
    }
    
    function _lookup($name)  // for namespaces + see cache
    {
        $where = array_search($name, $this->names);
        
        if ($where === FALSE || $where === NULL)
        {
            $where = count($this->names); $this->names[] = $name;
        }
        return $where;
    }
}

/*
<!--
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

  -->
*/
?>
