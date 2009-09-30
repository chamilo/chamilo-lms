<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * PEAR::HTML_Table makes the design of HTML tables easy, flexible, reusable and efficient.
 *
 * The PEAR::HTML_Table package provides methods for easy and efficient design of HTML tables.
 * - Lots of customization options.
 * - Tables can be modified at any time.
 * - The logic is the same as standard HTML editors.
 * - Handles col and rowspans.
 * - PHP code is shorter, easier to read and to maintain.
 * - Tables options can be reused.
 *
 * For auto filling of data and such then check out http://pear.php.net/package/HTML_Table_Matrix
 *
 * PHP versions 4
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   HTML
 * @package    HTML_Table
 * @author     Adam Daniel <adaniel1@eesus.jnj.com>
 * @author     Bertrand Mansion <bmansion@mamasam.com>
 * @copyright  2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: Table.php 9611 2006-10-20 11:47:56Z bmol $
 * @link       http://pear.php.net/package/HTML_Table
 */


/**
* Requires PEAR, HTML_Common and HTML_Table_Storage
*/
require_once 'PEAR.php';
require_once 'HTML/Common.php';
require_once 'HTML/Table/Storage.php';

/**
 * PEAR::HTML_Table makes the design of HTML tables easy, flexible, reusable and efficient.
 *
 * The PEAR::HTML_Table package provides methods for easy and efficient design of HTML tables.
 * - Lots of customization options.
 * - Tables can be modified at any time.
 * - The logic is the same as standard HTML editors.
 * - Handles col and rowspans.
 * - PHP code is shorter, easier to read and to maintain.
 * - Tables options can be reused.
 *
 * For auto filling of data and such then check out http://pear.php.net/package/HTML_Table_Matrix
 *
 * @category   HTML
 * @package    HTML_Table
 * @author     Adam Daniel <adaniel1@eesus.jnj.com>
 * @author     Bertrand Mansion <bmansion@mamasam.com>
 * @copyright  2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/HTML_Table
 */
class HTML_Table extends HTML_Common {

    /**
     * Value to insert into empty cells
     * @var    string
     * @access private
     */
    var $_autoFill = '&nbsp;';

    /**
     * Array containing the table caption
     * @var     array
     * @access  private
     */
    var $_caption = array();

    /**
     * Array containing the table column group specifications
     *
     * @var     array
     * @author  Laurent Laville (pear at laurent-laville dot org)
     * @access  private
     */
    var $_colgroup = array();

    /**
     * HTML_Table_Storage object for the (t)head of the table
     * @var    object
     * @access private
     */
    var $_thead = null;

    /**
     * HTML_Table_Storage object for the (t)foot of the table
     * @var    object
     * @access private
     */
    var $_tfoot = null;

    /**
     * HTML_Table_Storage object for the (t)body of the table
     * @var    object
     * @access private
     */
    var $_tbody = null;

    /**
     * Whether to use <thead>, <tfoot> and <tbody> or not
     * @var    bool
     * @access private
     */
    var $_useTGroups = false;

    /**
     * Class constructor
     * @param    array    $attributes        Associative array of table tag attributes
     * @param    int      $tabOffset         Tab offset of the table
     * @param    bool     $useTGroups        Whether to use <thead>, <tfoot> and
     *                                       <tbody> or not
     * @access   public
     */
    function HTML_Table($attributes = null, $tabOffset = 0, $useTGroups = false)
    {
        $commonVersion = 1.7;
        if (HTML_Common::apiVersion() < $commonVersion) {
            return PEAR::raiseError('HTML_Table version ' . $this->apiVersion() . ' requires ' .
                "HTML_Common version $commonVersion or greater.", 0, PEAR_ERROR_TRIGGER);
        }
        HTML_Common::HTML_Common($attributes, (int)$tabOffset);
        $this->_useTGroups = (boolean)$useTGroups;
        $this->_tbody =& new HTML_Table_Storage($attributes, $tabOffset, $this->_useTGroups);
        if ($this->_useTGroups) {
            $this->_thead =& new HTML_Table_Storage($attributes, $tabOffset, $this->_useTGroups);
            $this->_tfoot =& new HTML_Table_Storage($attributes, $tabOffset, $this->_useTGroups);
        }
    }

    /**
     * Returns the API version
     * @access  public
     * @return  double
     */
    function apiVersion()
    {
        return 1.7;
    }

    /**
     * Returns the HTML_Table_Storage object for <thead>
     * @access  public
     * @return  object
     */
    function &getHeader()
    {
        if (is_null($this->_thead)) {
            $this->_useTGroups = true;
            $this->_thead =& new HTML_Table_Storage($this->_attributes,
                $this->_tabOffset, $this->_useTGroups);
            $this->_tbody->setUseTGroups(true);
        }
        return $this->_thead;
    }

    /**
     * Returns the HTML_Table_Storage object for <tfoot>
     * @access  public
     * @return  object
     */
    function &getFooter()
    {
        if (is_null($this->_tfoot)) {
            $this->_useTGroups = true;
            $this->_tfoot =& new HTML_Table_Storage($this->_attributes,
                $this->_tabOffset, $this->_useTGroups);
            $this->_tbody->setUseTGroups(true);
        }
        return $this->_tfoot;
    }

    /**
     * Returns the HTML_Table_Storage object for <tbody>
     * (or the whole table if <t{head|foot|body> is not used)
     * @access  public
     * @return  object
     */
    function &getBody()
    {
        return $this->_tbody;
    }

    /**
     * Sets the table caption
     * @param   string    $caption
     * @param   mixed     $attributes        Associative array or string of table row attributes
     * @access  public
     */
    function setCaption($caption, $attributes = null)
    {
        $attributes = $this->_parseAttributes($attributes);
        $this->_caption = array('attr' => $attributes, 'contents' => $caption);
    }

    /**
     * Sets the table columns group specifications, or removes existing ones.
     *
     * @param   mixed     $colgroup         (optional) Columns attributes
     * @param   mixed     $attributes       (optional) Associative array or string
     *                                                 of table row attributes
     * @author  Laurent Laville (pear at laurent-laville dot org)
     * @access  public
     */
    function setColGroup($colgroup = null, $attributes = null)
    {
        if (isset($colgroup)) {
            $attributes = $this->_parseAttributes($attributes);
            $this->_colgroup[] = array('attr' => $attributes,
                                       'contents' => $colgroup);
        } else {
            $this->_colgroup = array();
        }
    }

    /**
     * Sets the autoFill value
     * @param   mixed   $fill
     * @access  public
     */
    function setAutoFill($fill)
    {
        $this->_tbody->setAutoFill($fill);
    }

    /**
     * Returns the autoFill value
     * @access   public
     * @return   mixed
     */
    function getAutoFill()
    {
        return $this->_tbody->getAutoFill();
    }

    /**
     * Sets the autoGrow value
     * @param    bool   $fill
     * @access   public
     */
    function setAutoGrow($grow)
    {
        $this->_tbody->setAutoGrow($grow);
    }

    /**
     * Returns the autoGrow value
     * @access   public
     * @return   mixed
     */
    function getAutoGrow()
    {
        return $this->_tbody->getAutoGrow();
    }

    /**
     * Sets the number of rows in the table
     * @param    int     $rows
     * @access   public
     */
    function setRowCount($rows)
    {
        $this->_tbody->setRowCount($rows);
    }

    /**
     * Sets the number of columns in the table
     * @param    int     $cols
     * @access   public
     */
    function setColCount($cols)
    {
        $this->_tbody->setColCount($cols);
    }

    /**
     * Returns the number of rows in the table
     * @access   public
     * @return   int
     */
    function getRowCount()
    {
        return $this->_tbody->getRowCount();
    }

    /**
     * Gets the number of columns in the table
     *
     * If a row index is specified, the count will not take
     * the spanned cells into account in the return value.
     *
     * @param    int    Row index to serve for cols count
     * @access   public
     * @return   int
     */
    function getColCount($row = null)
    {
        return $this->_tbody->getColCount($row);
    }

    /**
     * Sets a rows type 'TH' or 'TD'
     * @param    int         $row    Row index
     * @param    string      $type   'TH' or 'TD'
     * @access   public
     */

    function setRowType($row, $type)
    {
        $this->_tbody->setRowType($row, $type);
    }

    /**
     * Sets a columns type 'TH' or 'TD'
     * @param    int         $col    Column index
     * @param    string      $type   'TH' or 'TD'
     * @access   public
     */
    function setColType($col, $type)
    {
        $this->_tbody->setColType($col, $type);
    }

    /**
     * Sets the cell attributes for an existing cell.
     *
     * If the given indices do not exist and autoGrow is true then the given
     * row and/or col is automatically added.  If autoGrow is false then an
     * error is returned.
     * @param    int        $row         Row index
     * @param    int        $col         Column index
     * @param    mixed      $attributes  Associative array or string of table row attributes
     * @access   public
     * @throws   PEAR_Error
     */
    function setCellAttributes($row, $col, $attributes)
    {
        $ret = $this->_tbody->setCellAttributes($row, $col, $attributes);
        if (PEAR::isError($ret)) {
            return $ret;
        }
    }

    /**
     * Updates the cell attributes passed but leaves other existing attributes in tact
     * @param    int     $row         Row index
     * @param    int     $col         Column index
     * @param    mixed   $attributes  Associative array or string of table row attributes
     * @access   public
     */
    function updateCellAttributes($row, $col, $attributes)
    {
        $ret = $this->_tbody->updateCellAttributes($row, $col, $attributes);
        if (PEAR::isError($ret)) {
            return $ret;
        }
    }

    /**
     * Returns the attributes for a given cell
     * @param    int     $row         Row index
     * @param    int     $col         Column index
     * @return   array
     * @access   public
     */
    function getCellAttributes($row, $col)
    {
        return $this->_tbody->getCellAttributes($row, $col);
    }

    /**
     * Sets the cell contents for an existing cell
     *
     * If the given indices do not exist and autoGrow is true then the given
     * row and/or col is automatically added.  If autoGrow is false then an
     * error is returned.
     * @param    int      $row        Row index
     * @param    int      $col        Column index
     * @param    mixed    $contents   May contain html or any object with a toHTML method;
     *                                if it is an array (with strings and/or objects), $col
     *                                will be used as start offset and the array elements
     *                                will be set to this and the following columns in $row
     * @param    string   $type       (optional) Cell type either 'TH' or 'TD'
     * @access   public
     * @throws   PEAR_Error
     */
    function setCellContents($row, $col, $contents, $type = 'TD')
    {
        $ret = $this->_tbody->setCellContents($row, $col, $contents, $type);
        if (PEAR::isError($ret)) {
            return $ret;
        }
    }

    /**
     * Returns the cell contents for an existing cell
     * @param    int        $row    Row index
     * @param    int        $col    Column index
     * @access   public
     * @return   mixed
     */
    function getCellContents($row, $col)
    {
        return $this->_tbody->getCellContents($row, $col);
    }

    /**
     * Sets the contents of a header cell
     * @param    int     $row
     * @param    int     $col
     * @param    mixed   $contents
     * @param    mixed  $attributes Associative array or string of table row attributes
     * @access   public
     */
    function setHeaderContents($row, $col, $contents, $attributes = null)
    {
        $this->_tbody->setHeaderContents($row, $col, $contents, $attributes);
    }

    /**
     * Adds a table row and returns the row identifier
     * @param    array    $contents   (optional) Must be a indexed array of valid cell contents
     * @param    mixed    $attributes (optional) Associative array or string of table row attributes
     *                                This can also be an array of attributes, in which case the attributes
     *                                will be repeated in a loop.
     * @param    string   $type       (optional) Cell type either 'th' or 'td'
     * @param    bool     $inTR           false if attributes are to be applied in TD tags
     *                                    true if attributes are to be applied in TR tag
     * @return   int
     * @access   public
     */
    function addRow($contents = null, $attributes = null, $type = 'td', $inTR = false)
    {
        $ret = $this->_tbody->addRow($contents, $attributes, $type, $inTR);
        return $ret;
    }

    /**
     * Sets the row attributes for an existing row
     * @param    int      $row            Row index
     * @param    mixed    $attributes     Associative array or string of table row attributes
     *                                    This can also be an array of attributes, in which case the attributes
     *                                    will be repeated in a loop.
     * @param    bool     $inTR           false if attributes are to be applied in TD tags
     *                                    true if attributes are to be applied in TR tag
     * @access   public
     * @throws   PEAR_Error
     */
    function setRowAttributes($row, $attributes, $inTR = false)
    {
        $ret = $this->_tbody->setRowAttributes($row, $attributes, $inTR);
        if (PEAR::isError($ret)) {
            return $ret;
        }
    }

    /**
     * Updates the row attributes for an existing row
     * @param    int      $row            Row index
     * @param    mixed    $attributes     Associative array or string of table row attributes
     * @param    bool     $inTR           false if attributes are to be applied in TD tags
     *                                    true if attributes are to be applied in TR tag
     * @access   public
     * @throws   PEAR_Error
     */
    function updateRowAttributes($row, $attributes = null, $inTR = false)
    {
        $ret = $this->_tbody->updateRowAttributes($row, $attributes, $inTR);
        if (PEAR::isError($ret)) {
            return $ret;
        }
    }

    /**
     * Returns the attributes for a given row as contained in the TR tag
     * @param    int     $row         Row index
     * @return   array
     * @access   public
     */
    function getRowAttributes($row)
    {
        return $this->_tbody->getRowAttributes($row);
    }

    /**
     * Alternates the row attributes starting at $start
     * @param    int      $start          Row index of row in which alternating begins
     * @param    mixed    $attributes1    Associative array or string of table row attributes
     * @param    mixed    $attributes2    Associative array or string of table row attributes
     * @param    bool     $inTR           false if attributes are to be applied in TD tags
     *                                    true if attributes are to be applied in TR tag
     * @access   public
     */
    function altRowAttributes($start, $attributes1, $attributes2, $inTR = false)
    {
        $this->_tbody->altRowAttributes($start, $attributes1, $attributes2, $inTR);
    }

    /**
     * Adds a table column and returns the column identifier
     * @param    array    $contents   (optional) Must be a indexed array of valid cell contents
     * @param    mixed    $attributes (optional) Associative array or string of table row attributes
     * @param    string   $type       (optional) Cell type either 'th' or 'td'
     * @return   int
     * @access   public
     */
    function addCol($contents = null, $attributes = null, $type = 'td')
    {
        return $this->_tbody->addCol($contents, $attributes, $type);
    }

    /**
     * Sets the column attributes for an existing column
     * @param    int      $col            Column index
     * @param    mixed    $attributes     (optional) Associative array or string of table row attributes
     * @access   public
     */
    function setColAttributes($col, $attributes = null)
    {
        $this->_tbody->setColAttributes($col, $attributes);
    }

    /**
     * Updates the column attributes for an existing column
     * @param    int      $col            Column index
     * @param    mixed    $attributes     (optional) Associative array or string of table row attributes
     * @access   public
     */
    function updateColAttributes($col, $attributes = null)
    {
        $this->_tbody->updateColAttributes($col, $attributes);
    }

    /**
     * Sets the attributes for all cells
     * @param    mixed    $attributes        (optional) Associative array or string of table row attributes
     * @access   public
     */
    function setAllAttributes($attributes = null)
    {
        $this->_tbody->setAllAttributes($attributes);
    }

    /**
     * Updates the attributes for all cells
     * @param    mixed    $attributes        (optional) Associative array or string of table row attributes
     * @access   public
     */
    function updateAllAttributes($attributes = null)
    {
        $this->_tbody->updateAllAttributes($attributes);
    }

    /**
     * Returns the table structure as HTML
     * @access  public
     * @return  string
     */
    function toHtml()
    {
        $strHtml = '';
        $tabs = $this->_getTabs();
        $tab = $this->_getTab();
        $lnEnd = $this->_getLineEnd();
        if ($this->_comment) {
            $strHtml .= $tabs . "<!-- $this->_comment -->" . $lnEnd;
        }
        $strHtml .=
            $tabs . '<table' . $this->_getAttrString($this->_attributes) . '>' . $lnEnd;
        if (!empty($this->_caption)) {
            $attr = $this->_caption['attr'];
            $contents = $this->_caption['contents'];
            $strHtml .= $tabs . $tab . '<caption' . $this->_getAttrString($attr) . '>';
            if (is_array($contents)) {
                $contents = implode(', ', $contents);
            }
            $strHtml .= $contents;
            $strHtml .= '</caption>' . $lnEnd;
        }
        if (!empty($this->_colgroup)) {
            foreach ($this->_colgroup as $g => $col) {
                $attr = $this->_colgroup[$g]['attr'];
                $contents = $this->_colgroup[$g]['contents'];
                $strHtml .= $tabs . $tab . '<colgroup' . $this->_getAttrString($attr) . '>';
                if (!empty($contents)) {
                    $strHtml .= $lnEnd;
                    if (!is_array($contents)) {
                        $contents = array($contents);
                    }
                    foreach ($contents as $a => $colAttr) {
                        $attr = $this->_parseAttributes($colAttr);
                        $strHtml .= $tabs . $tab . $tab . '<col' . $this->_getAttrString($attr) . '>' . $lnEnd;
                    }
                    $strHtml .= $tabs . $tab;
                }
                $strHtml .= '</colgroup>' . $lnEnd;
            }
        }
        if ($this->_useTGroups) {
            $tHeadColCount = 0;
            if ($this->_thead !== null) {
                $tHeadColCount = $this->_thead->getColCount();
            }
            $tFootColCount = 0;
            if ($this->_tfoot !== null) {
                $tFootColCount = $this->_tfoot->getColCount();
            }
            $tBodyColCount = 0;
            if ($this->_tbody !== null) {
                $tBodyColCount = $this->_tbody->getColCount();
            }
            $maxColCount = max($tHeadColCount, $tFootColCount, $tBodyColCount);
            if ($this->_thead !== null) {
                $this->_thead->setColCount($maxColCount);
                if ($this->_thead->getRowCount() > 0) {
                    $strHtml .= $tabs . $tab . '<thead>' . $lnEnd;
                    $strHtml .= $this->_thead->toHtml($tabs, $tab);
                    $strHtml .= $tabs . $tab . '</thead>' . $lnEnd;
                }
            }
            if ($this->_tfoot !== null) {
                $this->_tfoot->setColCount($maxColCount);
                if ($this->_tfoot->getRowCount() > 0) {
                    $strHtml .= $tabs . $tab . '<tfoot>' . $lnEnd;
                    $strHtml .= $this->_tfoot->toHtml($tabs, $tab);
                    $strHtml .= $tabs . $tab . '</tfoot>' . $lnEnd;
                }
            }
            if ($this->_tbody !== null) {
                $this->_tbody->setColCount($maxColCount);
                if ($this->_tbody->getRowCount() > 0) {
                    $strHtml .= $tabs . $tab . '<tbody>' . $lnEnd;
                    $strHtml .= $this->_tbody->toHtml($tabs, $tab);
                    $strHtml .= $tabs . $tab . '</tbody>' . $lnEnd;
                }
            }
        } else {
            $strHtml .= $this->_tbody->toHtml($tabs, $tab);
        }
        $strHtml .= $tabs . '</table>' . $lnEnd;
        return $strHtml;
    }

}
?>