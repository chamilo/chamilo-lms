/**
 * JavaScript functions to handle standard behaviors of a QuickForm advmultiselect element
 *
 * @category   HTML
 * @package    HTML_QuickForm_advmultiselect
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @copyright  2007-2009 Laurent Laville
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    CVS: $Id: qfamsHandler.js,v 1.13 2009/02/06 09:49:22 farell Exp $
 * @since      File available since Release 1.3.0
 */

if (typeof QFAMS === "undefined" || !QFAMS) {
    /**
     * The QFAMS global namespace object.  If QFAMS is already defined, the
     * existing QFAMS object will not be overwritten so that defined
     * namespaces are preserved.
     * @class   QFAMS
     * @static
     * @public
     * @since   1.5.0
     */
    var QFAMS = {};
}

/**
 * QFAMS.env is used to keep track of end-user preferences
 * for persistant values.
 *
 * @class QFAMS.env
 * @static
 */
QFAMS.env = QFAMS.env || {
    /**
     * Keeps the persistant selection preference when items are selected or unselected
     *
     * @property persistantSelection
     * @type     Boolean
     */
    persistantSelection: false,

    /**
     * Keeps the persistant selection preference when items are moved up or down
     *
     * @property persistantMove
     * @type     Boolean
     */
    persistantMove: true
};

/**
 * Uses QFAMS.updateCounter as a
 * text tools to replace all childs of 'c' element by a new text node of 'v' value
 *
 * @param      dom element   c    html element; <span> is best use in most case
 * @param      string        v    new counter value
 *
 * @method     updateCounter
 * @static
 * @return     void
 * @public
 * @since      1.5.0
 */
QFAMS.updateCounter = function (c, v) {
    var i;
    var nodeText = null;

    if (c !== null) {
        // remove all previous child nodes of 'c' element
        if (c.childNodes) {
            for (i = 0; i < c.childNodes.length; i++) {
                c.removeChild(c.childNodes[i]);
            }
        }
        // add new text value 'v'
        nodeText = document.createTextNode(v);
        c.appendChild(nodeText);
    }
};

/**
 * Uses QFAMS.updateLiveCounter as a
 * standard onclick event handler to dynamic change value of counter
 * that display current selection
 *
 * @method     updateLiveCounter
 * @static
 * @return     void
 * @private
 * @since      1.5.0
 */
QFAMS.updateLiveCounter =  function () {
    var lbl           = this.parentNode;
    var selectedCount = 0;

    // Find all the checkboxes...
    var div    = lbl.parentNode;
    var inputs = div.getElementsByTagName('input');
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].checked == 1) {
            selectedCount++;
        }
    }
    var e         = div.id;
    var qfamsName = e.substring(e.indexOf('_', 0) + 1, e.length);
    // updates item count
    var span = document.getElementById(qfamsName + '_selected');
    QFAMS.updateCounter(span, selectedCount + '/' + inputs.length);
};

/**
 * Uses QFAMS.editSelection
 * in single select box mode, to edit current selection and update live counter
 *
 * @param      string        qfamsName      QuickForm advmultiselect element name
 * @param      integer       selectMode     Selection mode (0 = uncheck, 1 = check, 2 = toggle)
 *
 * @method     editSelection
 * @static
 * @return     void
 * @public
 * @since      1.5.0
 */
QFAMS.editSelection = function (qfamsName, selectMode) {
    if (selectMode !== 0 && selectMode !== 1 && selectMode !== 2) {
        return;
    }
    var selectedCount = 0;

    // Find all the checkboxes...
    var ams    = document.getElementById('qfams_' + qfamsName);
    var inputs = ams.getElementsByTagName('input');

    // Loop through all checkboxes (input element)
    for (var i = 0; i < inputs.length; i++) {
        if (selectMode === 2) {
            if (inputs[i].checked == 0) {
                inputs[i].checked = 1;
            } else if (inputs[i].checked == 1) {
                inputs[i].checked = 0;
            }
        } else {
            inputs[i].checked = selectMode;
        }
        if (inputs[i].checked == 1) {
            selectedCount++;
        }
    }

    // updates selected item count
    var span = document.getElementById(qfamsName + '_selected');
    QFAMS.updateCounter(span, selectedCount + '/' + inputs.length);
};

/**
 * Uses QFAMS.moveSelection
 * in double select box mode, to move current selection and update live counter
 *
 * @param      string        qfamsName      QuickForm advmultiselect element name
 * @param      dom element   selectLeft     Data source list
 * @param      dom element   selectRight    Target data list
 * @param      dom element   selectHidden   Full data source (selected, unselected)
 *                                          private usage
 * @param      string        action         Action name (add, remove, all, none, toggle)
 * @param      string        arrange        Sort option (none, asc, desc)
 *
 * @method     moveSelection
 * @static
 * @return     void
 * @public
 * @since      1.5.0
 */
QFAMS.moveSelection = function (qfamsName, selectLeft, selectRight, selectHidden, action, arrange) {
    var isIE = /*@cc_on!@*/false; //IE detector
    var source = null;
    var target = null;
    var option;
    var c      = null;
    var s      = null;
    var i;
    var maxFrom, maxTo;

    if (action === 'add' || action === 'all' || action === 'toggle') {
        source = selectLeft;
        target = selectRight;
    } else {
        source = selectRight;
        target = selectLeft;
    }
    // Don't do anything if nothing selected. Otherwise we throw javascript errors.
    if (source.selectedIndex === -1 && (action === 'add' || action === 'remove')) {
        return;
    }
    maxFrom = source.options.length;
    maxTo   = target.options.length;

    // check if target list is empty and remove fake empty option (tip to be XHTML compliant)
    if (maxTo > 0 && target.options[0].value === "") {
        target.removeAttribute("disabled");
        target.options[0] = null;
    }

    // Add items to the 'TO' list.
    for (i = (maxFrom - 1); i >= 0; i--) {
        if (action === 'all' || action === 'none' || action === 'toggle' || source.options[i].selected === true) {
            if (source.options[i].disabled === false) {
                if (isIE) {
                    option = source.options[i].removeNode(true);
                    option.selected = QFAMS.env.persistantSelection;
                    target.appendChild(option);
                } else {
                    option = source.options[i].cloneNode(true);
                    option.selected = QFAMS.env.persistantSelection;
                    target.options[target.options.length] = option;
                }
            }
        }
    }

    // Remove items from the 'FROM' list.
    if (!isIE) {
        for (i = (maxFrom - 1); i >= 0; i--) {
            if (action === 'all' || action === 'none' || action === 'toggle' || source.options[i].selected === true) {
                if (source.options[i].disabled === false) {
                    source.options[i] = null;
                }
            }
        }
    }

    // Add items to the 'FROM' list for toggle function
    if (action === 'toggle') {
        for (i = (maxTo - 1); i >= 0; i--) {
            if (target.options[i].disabled === false) {
                if (isIE) {
                    option = target.options[i].removeNode(true);
                    option.selected = QFAMS.env.persistantSelection;
                    source.appendChild(option);
                } else {
                    option = target.options[i].cloneNode(true);
                    option.selected = QFAMS.env.persistantSelection;
                    source.options[source.options.length] = option;
                }
            }
        }
        if (!isIE) {
            for (i = (maxTo - 1); i >= 0; i--) {
                if (target.options[i].disabled === false) {
                    target.options[i] = null;
                }
            }
        }
    }

    // updates unselected item count
    c = document.getElementById(qfamsName + '_unselected');
    s = document.getElementById(qfamsName + '-f');
    QFAMS.updateCounter(c, s.length);

    // updates selected item count
    c = document.getElementById(qfamsName + '_selected');
    s = document.getElementById(qfamsName + '-t');
    QFAMS.updateCounter(c, s.length);

    // Sort list if required
    if (arrange !== 'none') {
        QFAMS.sortList(target, QFAMS.compareText, arrange);
    }

    // Set the appropriate items as 'selected in the hidden select.
    // These are the values that will actually be posted with the form.
    QFAMS.updateHidden(selectHidden, selectRight);
};

/**
 * Uses QFAMS.sortList to
 * sort selection list if option is given in HTML_QuickForm_advmultiselect class constructor
 *
 * @param      dom element   list           Selection data list
 * @param      prototype     compareFunction to sort each element of a list
 * @param      string        arrange        Sort option (none, asc, desc)
 *
 * @method     sortList
 * @static
 * @return     void
 * @private
 * @since      1.5.0
 */
QFAMS.sortList = function (list, compareFunction, arrange)
{
    var i;
    var options = new Array(list.options.length);

    for (i = 0; i < options.length; i++) {
        options[i] = new Option(list.options[i].text,
                                list.options[i].value,
                                list.options[i].defaultSelected,
                                list.options[i].selected);
    }
    options.sort(compareFunction);
    if (arrange === 'desc') {
        options.reverse();
    }
    list.options.length = 0;
    for (i = 0; i < options.length; i++) {
        list.options[i] = options[i];
    }
};

/**
 * QFAMS.compareText
 * is a callback function to sort each element of two lists A and B
 *
 * @param      string        option1        single element of list A
 * @param      string        option2        single element of list B
 *
 * @method     compareText
 * @static
 * @return     integer       -1 if option1 is less than option2,
 *                            0 if option1 is equal to option2
 *                            1 if option1 is greater than option2
 * @private
 * @since      1.5.0
 */
QFAMS.compareText = function (option1, option2) {
    if (option1.text === option2.text) {
        return 0;
    }
    return option1.text < option2.text ? -1 : 1;
};

/**
 * QFAMS.updateHidden
 * updates the private list that handle selection of all elements (selected and unselected)
 *
 * @param      dom element   h              hidden list (contains all elements)
 * @param      dom element   r              selection list (contains only elements selected)
 *
 * @method     updateHidden
 * @static
 * @return     void
 * @private
 * @since      1.5.0
 */
QFAMS.updateHidden = function (h, r) {
    var i;

    for (i = 0; i < h.length; i++) {
        h.options[i].selected = false;
    }

    for (i = 0; i < r.length; i++) {
        h.options[h.length] = new Option(r.options[i].text, r.options[i].value);
        h.options[h.length - 1].selected = true;
    }
};

/**
 * With QFAMS.moveUp
 * end-user may arrange and element up to the selection list
 *
 * @param      dom element   l              selection list (contains only elements selected)
 * @param      dom element   h              hidden list (contains all elements)
 *
 * @method     moveUp
 * @static
 * @return     void
 * @public
 * @since      1.5.0
 */
QFAMS.moveUp = function (l, h) {
    var indice = l.selectedIndex;
    if (indice < 0) {
        return;
    }
    if (indice > 0) {
        QFAMS.moveSwap(l, indice, indice - 1);
        QFAMS.updateHidden(h, l);
    }
};

/**
 * With QFAMS.moveDown
 * end-user may arrange and element down to the selection list
 *
 * @param      dom element   l              selection list (contains only elements selected)
 * @param      dom element   h              hidden list (contains all elements)
 *
 * @method     moveDown
 * @static
 * @return     void
 * @public
 * @since      1.5.0
 */
QFAMS.moveDown = function (l, h) {
    var indice = l.selectedIndex;
    if (indice < 0) {
        return;
    }
    if (indice < l.options.length - 1) {
        QFAMS.moveSwap(l, indice, indice + 1);
        QFAMS.updateHidden(h, l);
    }
};

/**
 * With QFAMS.moveTop
 * end-user may arrange and element up to the top of selection list
 *
 * @param      dom element   l              selection list (contains only elements selected)
 * @param      dom element   h              hidden list (contains all elements)
 *
 * @method     moveTop
 * @static
 * @return     void
 * @public
 * @since      1.5.0
 */
QFAMS.moveTop = function (l, h) {
    var indice = l.selectedIndex;
    if (indice < 0) {
        return;
    }
    while (indice > 0) {
        QFAMS.moveSwap(l, indice, indice - 1);
        QFAMS.updateHidden(h, l);
        indice--;
    }
};

/**
 * With QFAMS.moveBottom
 * end-user may arrange and element down to the bottom of selection list
 *
 * @param      dom element   l              selection list (contains only elements selected)
 * @param      dom element   h              hidden list (contains all elements)
 *
 * @method     moveBottom
 * @static
 * @return     void
 * @public
 * @since      1.5.0
 */
QFAMS.moveBottom = function (l, h) {
    var indice = l.selectedIndex;
    if (indice < 0) {
        return;
    }
    while (indice < l.options.length - 1) {
        QFAMS.moveSwap(l, indice, indice + 1);
        QFAMS.updateHidden(h, l);
        indice++;
    }
};

/**
 * With QFAMS.moveSwap
 * end-user may invert two elements position in the selection list
 *
 * @param      dom element   l              selection list (contains only elements selected)
 * @param      integer       i              element source indice
 * @param      integer       j              element target indice
 *
 * @method     moveSwap
 * @static
 * @return     void
 * @public
 * @since      1.5.0
 */
QFAMS.moveSwap = function (l, i, j) {
    var node;

    node = l.replaceChild(l.options[i], l.options[j]);
    if (i > j) {
        l.insertBefore(node, l.options[j].nextSibling);
    } else {
        l.insertBefore(node, l.options[i]);
    }

    if (QFAMS.env.persistantMove) {
        l.selectedIndex = j;
    } else {
        l.selectedIndex = -1;
    }
};

/**
 * Uses QFAMS.init to
 * initialize onclick event handler for all checkbox element
 * of a QuickForm advmultiselect element with single select box.
 *
 * @method     init
 * @static
 * @return     void
 * @public
 * @since      1.5.0
 */
QFAMS.init = function (elm)
{
    var e, i;

    for (e = 0; e < elm.length; e++) {
        var div = document.getElementById('qfams_' + elm[e]);
        if (div !== null) {
            var inputs = div.getElementsByTagName('input');
            if (inputs !== null) {
                for (i = 0; i < inputs.length; i++) {
                    inputs[i].onclick = QFAMS.updateLiveCounter;
                }
            }
        }
    }
};

