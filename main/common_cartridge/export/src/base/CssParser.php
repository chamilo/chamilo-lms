<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/gral_lib/cssparser.php under GNU/GPL license */

class CssParser
{
    private $css;
    private $html;

    public function __construct($html = true)
    {
        $this->html = ($html != false);
        $this->clear();
    }

    public function clear()
    {
        unset($this->css);
        $this->css = [];
        if ($this->html) {
            $this->add("ADDRESS", "");
            $this->add("APPLET", "");
            $this->add("AREA", "");
            $this->add("A", "text-decoration : underline; color : Blue;");
            $this->add("A:visited", "color : Purple;");
            $this->add("BASE", "");
            $this->add("BASEFONT", "");
            $this->add("BIG", "");
            $this->add("BLOCKQUOTE", "");
            $this->add("BODY", "");
            $this->add("BR", "");
            $this->add("B", "font-weight: bold;");
            $this->add("CAPTION", "");
            $this->add("CENTER", "");
            $this->add("CITE", "");
            $this->add("CODE", "");
            $this->add("DD", "");
            $this->add("DFN", "");
            $this->add("DIR", "");
            $this->add("DIV", "");
            $this->add("DL", "");
            $this->add("DT", "");
            $this->add("EM", "");
            $this->add("FONT", "");
            $this->add("FORM", "");
            $this->add("H1", "");
            $this->add("H2", "");
            $this->add("H3", "");
            $this->add("H4", "");
            $this->add("H5", "");
            $this->add("H6", "");
            $this->add("HEAD", "");
            $this->add("HR", "");
            $this->add("HTML", "");
            $this->add("IMG", "");
            $this->add("INPUT", "");
            $this->add("ISINDEX", "");
            $this->add("I", "font-style: italic;");
            $this->add("KBD", "");
            $this->add("LINK", "");
            $this->add("LI", "");
            $this->add("MAP", "");
            $this->add("MENU", "");
            $this->add("META", "");
            $this->add("OL", "");
            $this->add("OPTION", "");
            $this->add("PARAM", "");
            $this->add("PRE", "");
            $this->add("P", "");
            $this->add("SAMP", "");
            $this->add("SCRIPT", "");
            $this->add("SELECT", "");
            $this->add("SMALL", "");
            $this->add("STRIKE", "");
            $this->add("STRONG", "");
            $this->add("STYLE", "");
            $this->add("SUB", "");
            $this->add("SUP", "");
            $this->add("TABLE", "");
            $this->add("TD", "");
            $this->add("TEXTAREA", "");
            $this->add("TH", "");
            $this->add("TITLE", "");
            $this->add("TR", "");
            $this->add("TT", "");
            $this->add("UL", "");
            $this->add("U", "text-decoration : underline;");
            $this->add("VAR", "");
        }
    }

    public function setHTML($html)
    {
        $this->html = ($html != false);
    }

    public function add($key, $codestr)
    {
        $key = strtolower($key);
        $codestr = strtolower($codestr);
        if (!isset($this->css[$key])) {
            $this->css[$key] = [];
        }
        $codes = explode(";", $codestr);
        if (count($codes) > 0) {
            $codekey = '';
            $codevalue = '';
            foreach ($codes as $code) {
                $code = trim($code);
                $this->assignValues(explode(":", $code), $codekey, $codevalue);
                if (strlen($codekey) > 0) {
                    $this->css[$key][trim($codekey)] = trim($codevalue);
                }
            }
        }
    }

    public function get($key, $property)
    {
        $key = strtolower($key);
        $property = strtolower($property);
        $tag = '';
        $subtag = '';
        $class = '';
        $id = '';
        $this->assignValues(explode(":", $key), $tag, $subtag);
        $this->assignValues(explode(".", $tag), $tag, $class);
        $this->assignValues(explode("#", $tag), $tag, $id);
        $result = "";
        $_subtag = '';
        $_class = '';
        $_id = '';
        foreach ($this->css as $_tag => $value) {
            $this->assignValues(explode(":", $_tag), $_tag, $_subtag);
            $this->assignValues(explode(".", $_tag), $_tag, $_class);
            $this->assignValues(explode("#", $_tag), $_tag, $_id);

            $tagmatch = (strcmp($tag, $_tag) == 0) | (strlen($_tag) == 0);
            $subtagmatch = (strcmp($subtag, $_subtag) == 0) | (strlen($_subtag) == 0);
            $classmatch = (strcmp($class, $_class) == 0) | (strlen($_class) == 0);
            $idmatch = (strcmp($id, $_id) == 0);

            if ($tagmatch & $subtagmatch & $classmatch & $idmatch) {
                $temp = $_tag;
                if ((strlen($temp) > 0) & (strlen($_class) > 0)) {
                    $temp .= ".".$_class;
                } elseif (strlen($temp) == 0) {
                    $temp = ".".$_class;
                }
                if ((strlen($temp) > 0) & (strlen($_subtag) > 0)) {
                    $temp .= ":".$_subtag;
                } elseif (strlen($temp) == 0) {
                    $temp = ":".$_subtag;
                }
                if (isset($this->css[$temp][$property])) {
                    $result = $this->css[$temp][$property];
                }
            }
        }

        return $result;
    }

    public function getSection($key)
    {
        $key = strtolower($key);
        $tag = '';
        $subtag = '';
        $class = '';
        $id = '';
        $_subtag = '';
        $_class = '';
        $_id = '';

        $this->assignValues(explode(":", $key), $tag, $subtag);
        $this->assignValues(explode(".", $tag), $tag, $class);
        $this->assignValues(explode("#", $tag), $tag, $id);
        $result = [];
        foreach ($this->css as $_tag => $value) {
            $this->assignValues(explode(":", $_tag), $_tag, $_subtag);
            $this->assignValues(explode(".", $_tag), $_tag, $_class);
            $this->assignValues(explode("#", $_tag), $_tag, $_id);

            $tagmatch = (strcmp($tag, $_tag) == 0) | (strlen($_tag) == 0);
            $subtagmatch = (strcmp($subtag, $_subtag) == 0) | (strlen($_subtag) == 0);
            $classmatch = (strcmp($class, $_class) == 0) | (strlen($_class) == 0);
            $idmatch = (strcmp($id, $_id) == 0);

            if ($tagmatch & $subtagmatch & $classmatch & $idmatch) {
                $temp = $_tag;
                if ((strlen($temp) > 0) & (strlen($_class) > 0)) {
                    $temp .= ".".$_class;
                } elseif (strlen($temp) == 0) {
                    $temp = ".".$_class;
                }
                if ((strlen($temp) > 0) & (strlen($_subtag) > 0)) {
                    $temp .= ":".$_subtag;
                } elseif (strlen($temp) == 0) {
                    $temp = ":".$_subtag;
                }
                foreach ($this->css[$temp] as $property => $value) {
                    $result[$property] = $value;
                }
            }
        }

        return $result;
    }

    public function parseStr($str)
    {
        $this->clear();
        // Remove comments
        $str = preg_replace("/\/\*(.*)?\*\//Usi", "", $str);
        // Parse this damn csscode
        $parts = explode("}", $str);
        if (count($parts) > 0) {
            foreach ($parts as $part) {
                $keystr = '';
                $codestr = '';
                $this->assignValues(explode("{", $part), $keystr, $codestr);
                $keys = explode(",", trim($keystr));
                if (count($keys) > 0) {
                    foreach ($keys as $key) {
                        if (strlen($key) > 0) {
                            $key = str_replace("\n", "", $key);
                            $key = str_replace("\\", "", $key);
                            $this->Add($key, trim($codestr));
                        }
                    }
                }
            }
        }

        return count($this->css) > 0;
    }

    public function parse($filename)
    {
        $this->clear();
        if (file_exists($filename)) {
            return $this->parseStr(file_get_contents($filename));
        } else {
            return false;
        }
    }

    public function getCSS()
    {
        $result = "";
        foreach ($this->css as $key => $values) {
            $result .= $key." {\n";
            foreach ($values as $key => $value) {
                $result .= "  $key: $value;\n";
            }
            $result .= "}\n\n";
        }

        return $result;
    }

    private function assignValues($arr, &$val1, &$val2)
    {
        $n = count($arr);
        if ($n > 0) {
            $val1 = $arr[0];
            $val2 = ($n > 1) ? $arr[1] : '';
        }
    }
}
