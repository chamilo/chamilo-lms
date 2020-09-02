<?php

/**
 * Storage class for HTML::Table data
 *
 * This class stores data for tables built with HTML_Table. When having
 * more than one instance, it can be used for grouping the table into the
 * parts <thead>...</thead>, <tfoot>...</tfoot> and <tbody>...</tbody>.
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 *
 * Copyright (c) 2005-2007, Adam Daniel <adaniel1@eesus.jnj.com>,
 *                          Bertrand Mansion <bmansion@mamasam.com>,
 *                          Mark Wiesemann <wiesemann@php.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   HTML
 * @package    HTML_Table
 * @author     Adam Daniel <adaniel1@eesus.jnj.com>
 * @author     Bertrand Mansion <bmansion@mamasam.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Storage.php,v 1.16 2007/04/29 16:31:06 wiesemann Exp $
 * @link       http://pear.php.net/package/HTML_Table
 */

/**
 * Storage class for HTML::Table data
 *
 * This class stores data for tables built with HTML_Table. When having
 * more than one instance, it can be used for grouping the table into the
 * parts <thead>...</thead>, <tfoot>...</tfoot> and <tbody>...</tbody>.
 *
 * @category   HTML
 * @package    HTML_Table
 * @author     Adam Daniel <adaniel1@eesus.jnj.com>
 * @author     Bertrand Mansion <bmansion@mamasam.com>
 * @author     Mark Wiesemann <wiesemann@php.net>
 * @copyright  2005-2006 The PHP Group
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/HTML_Table
 */
class HTML_Table_Storage extends HTML_Common
{
    /**
     * Value to insert into empty cells
     * @var    string
     * @access private
     */
    var $_autoFill = '&nbsp;';

    /**
     * Automatically adds a new row or column if a given row or column index
     * does not exist
     * @var    bool
     * @access private
     */
    var $_autoGrow = true;

    /**
     * Array containing the table structure
     * @var     array
     * @access  private
     */
    var $_structure = array();

    /**
     * Number of rows composing in the table
     * @var     int
     * @access  private
     */
    var $_rows = 0;

    /**
     * Number of column composing the table
     * @var     int
     * @access  private
     */
    var $_cols = 0;

    /**
     * Tracks the level of nested tables
     * @var    int
     * @access private
     */
    var $_nestLevel = 0;

    /**
     * Whether to use <thead>, <tfoot> and <tbody> or not
     * @var    bool
     * @access private
     */
    var $_useTGroups = false;

    /**
     * Class constructor
     * @param    int      $tabOffset
     * @param    bool     $useTGroups        Whether to use <thead>, <tfoot> and
     *                                       <tbody> or not
     * @access   public
     */
    public function __construct($tabOffset = 0, $useTGroups = false)
    {
        parent::__construct(null, (int)$tabOffset);
        $this->_useTGroups = (boolean)$useTGroups;
    }

    /**
     * Sets the useTGroups value
     * @param   boolean   $useTGroups
     * @access  public
     */
    public function setUseTGroups($useTGroups)
    {
        $this->_useTGroups = $useTGroups;
    }

    /**
     * Returns the useTGroups value
     * @access   public
     * @return   boolean
     */
    public function getUseTGroups()
    {
        return $this->_useTGroups;
    }

    /**
     * Sets the autoFill value
     * @param   mixed   $fill
     * @access  public
     */
    public function setAutoFill($fill)
    {
        $this->_autoFill = $fill;
    }

    /**
     * Returns the autoFill value
     * @access   public
     * @return   mixed
     */
    public function getAutoFill()
    {
        return $this->_autoFill;
    }

    /**
     * Sets the autoGrow value
     * @param    bool   $fill
     * @access   public
     */
    public function setAutoGrow($grow)
    {
        $this->_autoGrow = $grow;
    }

    /**
     * Returns the autoGrow value
     * @access   public
     * @return   mixed
     */
    public function getAutoGrow()
    {
        return $this->_autoGrow;
    }

    /**
     * Sets the number of rows in the table
     * @param    int     $rows
     * @access   public
     */
    function setRowCount($rows)
    {
        $this->_rows = $rows;
    }

    /**
     * Sets the number of columns in the table
     * @param    int     $cols
     * @access   public
     */
    function setColCount($cols)
    {
        $this->_cols = $cols;
    }

    /**
     * Returns the number of rows in the table
     * @access   public
     * @return   int
     */
    function getRowCount()
    {
        return $this->_rows;
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
        if (!is_null($row)) {
            $count = 0;
            foreach ($this->_structure[$row] as $cell) {
                if (is_array($cell)) {
                    $count++;
                }
            }
            return $count;
        }
        return $this->_cols;
    }

    /**
     * Sets a rows type 'TH' or 'TD'
     * @param    int         $row    Row index
     * @param    string      $type   'TH' or 'TD'
     * @access   public
     */

    function setRowType($row, $type)
    {
        for ($counter = 0; $counter < $this->_cols; $counter++) {
            $this->_structure[$row][$counter]['type'] = $type;
        }
    }

    /**
     * Sets a columns type 'TH' or 'TD'
     * @param    int         $col    Column index
     * @param    string      $type   'TH' or 'TD'
     * @access   public
     */
    function setColType($col, $type)
    {
        for ($counter = 0; $counter < $this->_rows; $counter++) {
            $this->_structure[$counter][$col]['type'] = $type;
        }
    }

    /**
     * Sets the cell attributes for an existing cell.
     *
     * If the given indices do not exist and autoGrow is true then the given
     * row and/or col is automatically added.  If autoGrow is false then an
     * error is returned.
     * @param    int        $row         Row index
     * @param    int        $col         Column index
     * @param    mixed      $attributes  Associative array or string of table
     *                                   row attributes
     * @access   public
     * @throws   PEAR_Error
     */
    function setCellAttributes($row, $col, $attributes)
    {
        if (   isset($this->_structure[$row][$col])
            && $this->_structure[$row][$col] == '__SPANNED__'
        ) {
            return;
        }
        $attributes = $this->_parseAttributes($attributes);
        $err = $this->_adjustEnds($row, $col, 'setCellAttributes', $attributes);
        if (PEAR::isError($err)) {
            return $err;
        }
        $this->_structure[$row][$col]['attr'] = $attributes;
        // Fix use of rowspan/colspan
        //$this->_updateSpanGrid($row, $col);
    }

    /**
     * Updates the cell attributes passed but leaves other existing attributes
     * intact
     * @param    int     $row         Row index
     * @param    int     $col         Column index
     * @param    mixed   $attributes  Associative array or string of table row
     *                                attributes
     * @access   public
     */
    function updateCellAttributes($row, $col, $attributes)
    {
        if (   isset($this->_structure[$row][$col])
            && $this->_structure[$row][$col] == '__SPANNED__'
        ) {
            return;
        }
        $attributes = $this->_parseAttributes($attributes);
        $err = $this->_adjustEnds($row, $col, 'updateCellAttributes', $attributes);
        if (PEAR::isError($err)) {
            return $err;
        }
        $this->_updateAttrArray($this->_structure[$row][$col]['attr'], $attributes);
        //$this->_updateSpanGrid($row, $col);
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
        if (   isset($this->_structure[$row][$col])
            && $this->_structure[$row][$col] != '__SPANNED__'
        ) {
            return $this->_structure[$row][$col]['attr'];
        } elseif (!isset($this->_structure[$row][$col])) {
            throw new \Exception('Invalid table cell reference[' .$row . '][' . $col . '] in HTML_Table::getCellAttributes');
        }
    }

    /**
     * Sets the cell contents for an existing cell
     *
     * If the given indices do not exist and autoGrow is true then the given
     * row and/or col is automatically added.  If autoGrow is false then an
     * error is returned.
     * @param    int      $row        Row index
     * @param    int      $col        Column index
     * @param    mixed    $contents   May contain html or any object with a
     *                                toHTML() method; if it is an array (with
     *                                strings and/or objects), $col will be used
     *                                as start offset and the array elements will
     *                                be set to this and the following columns
     *                                in $row
     * @param    string   $type       (optional) Cell type either 'TH' or 'TD'
     * @access   public
     * @throws   PEAR_Error
     */
    function setCellContents($row, $col, $contents, $type = 'TD')
    {
        if (is_array($contents)) {
            foreach ($contents as $singleContent) {
                $ret = $this->_setSingleCellContents($row, $col, $singleContent,
                    $type);
                if (PEAR::isError($ret)) {
                    return $ret;
                }
                $col++;
            }
        } else {
            $ret = $this->_setSingleCellContents($row, $col, $contents, $type);
            if (PEAR::isError($ret)) {
                return $ret;
            }
        }
    }

    /**
     * Sets the cell contents for a single existing cell
     *
     * If the given indices do not exist and autoGrow is true then the given
     * row and/or col is automatically added.  If autoGrow is false then an
     * error is returned.
     * @param    int      $row        Row index
     * @param    int      $col        Column index
     * @param    mixed    $contents   May contain html or any object with a
     *                                toHTML() method; if it is an array (with
     *                                strings and/or objects), $col will be used
     *                                as start offset and the array elements will
     *                                be set to this and the following columns
     *                                in $row
     * @param    string   $type       (optional) Cell type either 'TH' or 'TD'
     * @access   private
     * @throws   PEAR_Error
     */
    function _setSingleCellContents($row, $col, $contents, $type = 'TD')
    {
        if (   isset($this->_structure[$row][$col])
            && $this->_structure[$row][$col] == '__SPANNED__'
        ) {
            return;
        }
        $err = $this->_adjustEnds($row, $col, 'setCellContents');
        if (PEAR::isError($err)) {
            return $err;
        }
        $this->_structure[$row][$col]['contents'] = $contents;
        $this->_structure[$row][$col]['type'] = $type;
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
        if (   isset($this->_structure[$row][$col])
            && $this->_structure[$row][$col] == '__SPANNED__'
        ) {
            return;
        }
        if (!isset($this->_structure[$row][$col])) {
            throw new \Exception('Invalid table cell reference[' .$row . '][' . $col . '] in HTML_Table::getCellContents');
        }
        return $this->_structure[$row][$col]['contents'];
    }

    /**
     * Sets the contents of a header cell
     * @param    int     $row
     * @param    int     $col
     * @param    mixed   $contents
     * @param    mixed   $attributes  Associative array or string of table row
     *                                attributes
     * @access   public
     */
    function setHeaderContents($row, $col, $contents, $attributes = null)
    {
        $this->setCellContents($row, $col, $contents, 'TH');
        if (!is_null($attributes)) {
            $this->updateCellAttributes($row, $col, $attributes);
        }
    }

    /**
     * Adds a table row and returns the row identifier
     * @param    array    $contents   (optional) Must be a indexed array of valid
     *                                           cell contents
     * @param    mixed    $attributes (optional) Associative array or string of
     *                                           table row attributes. This can
     *                                           also be an array of attributes,
     *                                           in which case the attributes
     *                                           will be repeated in a loop.
     * @param    string   $type       (optional) Cell type either 'th' or 'td'
     * @param    bool     $inTR                  false if attributes are to be
     *                                           applied in TD tags; true if
     *                                           attributes are to be applied in
     *                                            TR tag
     * @return   int
     * @access   public
     */
    function addRow($contents = [], $attributes = null, $type = 'td',
        $inTR = false)
    {
        if (isset($contents) && !is_array($contents)) {
            throw new \Exception('First parameter to HTML_Table::addRow must be an array');
        }
        if (is_null($contents)) {
            $contents = array();
        }

        $type = strtolower($type);
        $row = $this->_rows++;
        foreach ($contents as $col => $content) {
            if ($type == 'td') {
                $this->setCellContents($row, $col, $content);
            } elseif ($type == 'th') {
                $this->setHeaderContents($row, $col, $content);
            }
        }
        $this->setRowAttributes($row, $attributes, $inTR);
        return $row;
    }

    /**
     * Sets the row attributes for an existing row
     * @param    int      $row            Row index
     * @param    mixed    $attributes     Associative array or string of table
     *                                    row attributes. This can also be an
     *                                    array of attributes, in which case the
     *                                    attributes will be repeated in a loop.
     * @param    bool     $inTR           false if attributes are to be applied
     *                                    in TD tags; true if attributes are to
     *                                    be applied in TR tag
     * @access   public
     * @throws   PEAR_Error
     */
    function setRowAttributes($row, $attributes, $inTR = false)
    {
        if (!$inTR) {
            $multiAttr = $this->_isAttributesArray($attributes);
            for ($i = 0; $i < $this->_cols; $i++) {
                if ($multiAttr) {
                    $this->setCellAttributes($row, $i,
                        $attributes[$i - ((ceil(($i + 1) / count($attributes))) - 1) * count($attributes)]);
                } else {
                    $this->setCellAttributes($row, $i, $attributes);
                }
            }
        } else {
            $attributes = $this->_parseAttributes($attributes);
            $err = $this->_adjustEnds($row, 0, 'setRowAttributes', $attributes);
            if (PEAR::isError($err)) {
                return $err;
            }
            $this->_structure[$row]['attr'] = $attributes;
        }
    }

    /**
     * Updates the row attributes for an existing row
     * @param    int      $row            Row index
     * @param    mixed    $attributes     Associative array or string of table
     *                                    row attributes
     * @param    bool     $inTR           false if attributes are to be applied
     *                                    in TD tags; true if attributes are to
     *                                    be applied in TR tag
     * @access   public
     * @throws   PEAR_Error
     */
    function updateRowAttributes($row, $attributes = null, $inTR = false)
    {
        if (!$inTR) {
            $multiAttr = $this->_isAttributesArray($attributes);
            for ($i = 0; $i < $this->_cols; $i++) {
                if ($multiAttr) {
                    $this->updateCellAttributes($row, $i,
                        $attributes[$i - ((ceil(($i + 1) / count($attributes))) - 1) * count($attributes)]);
                } else {
                    $this->updateCellAttributes($row, $i, $attributes);
                }
            }
        } else {
            $attributes = $this->_parseAttributes($attributes);
            $err = $this->_adjustEnds($row, 0, 'updateRowAttributes', $attributes);
            if (PEAR::isError($err)) {
                return $err;
            }
            $this->_updateAttrArray($this->_structure[$row]['attr'], $attributes);
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
        if (isset($this->_structure[$row]['attr'])) {
            return $this->_structure[$row]['attr'];
        }
        return;
    }

    /**
     * Alternates the row attributes starting at $start
     * @param    int      $start            Row index of row in which alternating
     *                                      begins
     * @param    mixed    $attributes1      Associative array or string of table
     *                                      row attributes
     * @param    mixed    $attributes2      Associative array or string of table
     *                                      row attributes
     * @param    bool     $inTR             false if attributes are to be applied
     *                                      in TD tags; true if attributes are to
     *                                      be applied in TR tag
     * @param    int      $firstAttributes  (optional) Which attributes should be
     *                                      applied to the first row, 1 or 2.
     * @access   public
     */
    function altRowAttributes($start, $attributes1, $attributes2, $inTR = false,
        $firstAttributes = 1)
    {
        for ($row = $start; $row < $this->_rows; $row++) {
            if (($row + $start + ($firstAttributes - 1)) % 2 == 0) {
                $attributes = $attributes1;
            } else {
                $attributes = $attributes2;
            }
            $this->updateRowAttributes($row, $attributes, $inTR);
        }
    }

    /**
     * Adds a table column and returns the column identifier
     * @param    array    $contents   (optional) Must be a indexed array of valid
     *                                cell contents
     * @param    mixed    $attributes (optional) Associative array or string of
     *                                table row attributes
     * @param    string   $type       (optional) Cell type either 'th' or 'td'
     * @return   int
     * @access   public
     */
    function addCol($contents = null, $attributes = null, $type = 'td')
    {
        if (isset($contents) && !is_array($contents)) {
            throw new \Exception('First parameter to HTML_Table::addCol must be an array');
        }
        if (is_null($contents)) {
            $contents = array();
        }

        $type = strtolower($type);
        $col = $this->_cols++;
        foreach ($contents as $row => $content) {
            if ($type == 'td') {
                $this->setCellContents($row, $col, $content);
            } elseif ($type == 'th') {
                $this->setHeaderContents($row, $col, $content);
            }
        }
        $this->setColAttributes($col, $attributes);
        return $col;
    }

    /**
     * Sets the column attributes for an existing column
     * @param    int      $col            Column index
     * @param    mixed    $attributes     (optional) Associative array or string
     *                                    of table row attributes
     * @access   public
     */
    function setColAttributes($col, $attributes = null)
    {
        $multiAttr = $this->_isAttributesArray($attributes);
        for ($i = 0; $i < $this->_rows; $i++) {
            if ($multiAttr) {
                $this->setCellAttributes($i, $col,
                    $attributes[$i - ((ceil(($i + 1) / count($attributes))) - 1) * count($attributes)]);
            } else {
                $this->setCellAttributes($i, $col, $attributes);
            }
        }
    }

    /**
     * Updates the column attributes for an existing column
     * @param    int      $col            Column index
     * @param    mixed    $attributes     (optional) Associative array or string
     *                                    of table row attributes
     * @access   public
     */
    function updateColAttributes($col, $attributes = null)
    {
        $multiAttr = $this->_isAttributesArray($attributes);
        for ($i = 0; $i < $this->_rows; $i++) {
            if ($multiAttr) {
                $this->updateCellAttributes($i, $col,
                    $attributes[$i - ((ceil(($i + 1) / count($attributes))) - 1) * count($attributes)]);
            } else {
                $this->updateCellAttributes($i, $col, $attributes);
            }
        }
    }

    /**
     * Sets the attributes for all cells
     * @param    mixed    $attributes        (optional) Associative array or
     *                                       string of table row attributes
     * @access   public
     */
    function setAllAttributes($attributes = null)
    {
        for ($i = 0; $i < $this->_rows; $i++) {
            $this->setRowAttributes($i, $attributes);
        }
    }

    /**
     * Updates the attributes for all cells
     * @param    mixed    $attributes        (optional) Associative array or
     *                                       string of table row attributes
     * @access   public
     */
    function updateAllAttributes($attributes = null)
    {
        for ($i = 0; $i < $this->_rows; $i++) {
            $this->updateRowAttributes($i, $attributes);
        }
    }

    /**
     * Returns the table rows as HTML
     * @access   public
     * @return   string
     */
    function toHtml($tabs = null, $tab = null)
    {
        $strHtml = '';
        if (is_null($tabs)) {
            $tabs = $this->_getTabs();
        }
        if (is_null($tab)) {
            $tab = $this->_getTab();
        }
        $lnEnd = $this->_getLineEnd();
        if ($this->_useTGroups) {
            $extraTab = $tab;
        } else {
            $extraTab = '';
        }
        if ($this->_cols > 0) {
            for ($i = 0 ; $i < $this->_rows ; $i++) {
                $attr = '';
                if (isset($this->_structure[$i]['attr'])) {
                    $attr = $this->_getAttrString($this->_structure[$i]['attr']);
                }
                $strHtml .= $tabs .$tab . $extraTab . '<tr'.$attr.'>' . $lnEnd;
                for ($j = 0 ; $j < $this->_cols ; $j++) {
                    $attr     = '';
                    $contents = '';
                    $type     = 'td';
                    if (isset($this->_structure[$i][$j]) && $this->_structure[$i][$j] == '__SPANNED__') {
                        continue;
                    }
                    if (isset($this->_structure[$i][$j]['type'])) {
                        $type = (strtolower($this->_structure[$i][$j]['type']) == 'th' ? 'th' : 'td');
                    }
                    if (isset($this->_structure[$i][$j]['attr'])) {
                        $attr = $this->_structure[$i][$j]['attr'];
                    }
                    if (isset($this->_structure[$i][$j]['contents'])) {
                        $contents = $this->_structure[$i][$j]['contents'];
                    }

                    if (is_object($contents)) {
                        // changes indent and line end settings on nested tables
                        if (is_subclass_of($contents, 'html_common')) {
                            $contents->setTab($tab . $extraTab);
                            $contents->setTabOffset($this->_tabOffset + 3);
                            $contents->_nestLevel = $this->_nestLevel + 1;
                            $contents->setLineEnd($this->_getLineEnd());
                        }
                        if (method_exists($contents, 'toHtml')) {
                            $contents = $contents->toHtml();
                        } elseif (method_exists($contents, 'toString')) {
                            $contents = $contents->toString();
                        }
                    }
                    if (is_array($contents)) {
                        $contents = implode(', ', $contents);
                    }

                    $typeContent = $tabs . $tab . $tab . $extraTab . "<$type" . $this->_getAttrString($attr) . '>';

                    if ($contents || is_numeric($contents)) {
                        $typeContent .= $contents;
                    } elseif (empty($contents)) {
                        if (isset($this->_autoFill) && $this->_autoFill) {
                            $contents = $this->_autoFill;
                        }
                    }

                    $typeContent .= "</$type>" . $lnEnd;

                    if (!empty($contents) || is_numeric($contents)) {
                        $strHtml .= $typeContent;
                    }
                }
                $strHtml .= $tabs . $tab . $extraTab . '</tr>' . $lnEnd;
            }
        }
        return $strHtml;
    }

    function toArray($tabs = null, $tab = null)
    {
        $data = [];
        if ($this->_cols > 0) {
            for ($i = 0 ; $i < $this->_rows ; $i++) {
                $item = [];
                for ($j = 0 ; $j < $this->_cols ; $j++) {
                    $contents = '';
                    if (isset($this->_structure[$i][$j]) && $this->_structure[$i][$j] == '__SPANNED__') {
                        continue;
                    }

                    if (isset($this->_structure[$i][$j]['contents'])) {
                        $contents = $this->_structure[$i][$j]['contents'];
                    }

                    if (is_object($contents)) {
                        if (method_exists($contents, 'toHtml')) {
                            $contents = $contents->toHtml();
                        } elseif (method_exists($contents, 'toString')) {
                            $contents = $contents->toString();
                        }
                        $item = $contents;
                    }
                    if (is_array($contents)) {
                        $contents = implode(', ', $contents);
                    }
                    $item[] = $contents;
                }
                $data[] = $item;
            }
        }

        return $data;
    }

    /**
     * Checks if rows or columns are spanned
     * @param    int        $row            Row index
     * @param    int        $col            Column index
     * @access   private
     */
    function _updateSpanGrid($row, $col)
    {
        if (isset($this->_structure[$row][$col]['attr']['colspan'])) {
            $colspan = $this->_structure[$row][$col]['attr']['colspan'];
        }

        if (isset($this->_structure[$row][$col]['attr']['rowspan'])) {
            $rowspan = $this->_structure[$row][$col]['attr']['rowspan'];
        }

        if (isset($colspan)) {
            for ($j = $col + 1; (($j < $this->_cols) && ($j <= ($col + $colspan - 1))); $j++) {
                $this->_structure[$row][$j] = '__SPANNED__';
            }
        }

        if (isset($rowspan)) {
            for ($i = $row + 1; (($i < $this->_rows) && ($i <= ($row + $rowspan - 1))); $i++) {
                $this->_structure[$i][$col] = '__SPANNED__';
            }
        }

        if (isset($colspan) && isset($rowspan)) {
            for ($i = $row + 1; (($i < $this->_rows) && ($i <= ($row + $rowspan - 1))); $i++) {
                for ($j = $col + 1; (($j <= $this->_cols) && ($j <= ($col + $colspan - 1))); $j++) {
                    $this->_structure[$i][$j] = '__SPANNED__';
                }
            }
        }
    }

    /**
     * Adjusts ends (total number of rows and columns)
     * @param    int     $row        Row index
     * @param    int     $col        Column index
     * @param    string  $method     Method name of caller
     *                               Used to populate PEAR_Error if thrown.
     * @param    array   $attributes Assoc array of attributes
     *                               Default is an empty array.
     * @access   private
     * @throws   PEAR_Error
     */
    function _adjustEnds($row, $col, $method, $attributes = array())
    {
        $colspan = isset($attributes['colspan']) ? $attributes['colspan'] : 1;
        $rowspan = isset($attributes['rowspan']) ? $attributes['rowspan'] : 1;
        if (!is_numeric($row) or !is_numeric($col)) {
            //throw new Exception('Row or column index is not numerical');
            return;
        }
        if (($row + $rowspan - 1) >= $this->_rows) {
            if ($this->_autoGrow) {
                $this->_rows = $row + $rowspan;

            } else {
                /*return PEAR::raiseError('Invalid table row reference[' .
                    $row . '] in HTML_Table::' . $method);*/
            }
        }

        if (($col + $colspan - 1) >= $this->_cols) {
            if ($this->_autoGrow) {
                $this->_cols = $col + $colspan;
            } else {
                /*return PEAR::raiseError('Invalid table column reference[' .
                    $col . '] in HTML_Table::' . $method);*/
            }
        }
    }

    /**
     * Tells if the parameter is an array of attribute arrays/strings
     * @param    mixed   $attributes Variable to test
     * @access   private
     * @return   bool
     */
    function _isAttributesArray($attributes)
    {
        if (is_array($attributes) && isset($attributes[0])) {
            if (is_array($attributes[0]) || (is_string($attributes[0]) && count($attributes) > 1)) {
                return true;
            }
        }
        return false;
    }

}
?>
