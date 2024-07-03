/**
 * FILE: jQuery.ptTileSelect.js
 *
 * @fileOverview
 * jQuery plugin for displaying a popup that allows a user
 * to define a time and set that time back to a form's input
 * field.
 *
 * @version 0.8
 * @author  Paul Tavares, www.purtuga.com
 * @see     http://pttimeselect.sourceforge.net
 *
 * @requires jQuery {@link http://www.jquery.com}
 *
 *
 * LICENSE:
 *
 *  Copyright (c) 2007 Paul T. (purtuga.com)
 *  Dual licensed under the:
 *
 *  -   MIT
 *      <http://www.opensource.org/licenses/mit-license.php>
 *
 *  -   GPL
 *      <http://www.opensource.org/licenses/gpl-license.php>
 *
 *  User can pick whichever one applies best for their project
 *  and doesn not have to contact me.
 *
 *
 * INSTALLATION:
 *
 * There are two files (.css and .js) delivered with this plugin and
 * that must be included in your html page after the jquery.js library
 * and the jQuery UI style sheet (the jQuery UI javascript library is
 * not necessary).
 * Both of these are to be included inside of the 'head' element of
 * the document. Example below demonstrates this along side the jQuery
 * libraries.
 *
 * |    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
 * |    <link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/redmond/jquery-ui.css" />
 * |
 * |    <link rel="stylesheet" type="text/css" href="jquery.ptTimeSelect.css" />
 * |    <script type="text/javascript" src="jquery.ptTimeSelect.js"></script>
 * |
 *
 * USAGE:
 *
 *     -    See <$(ele).ptTimeSelect()>
 *
 *
 *
 * LAST UPDATED:
 *
 *         - $Date: 2012/08/05 19:40:21 $
 *         - $Author: paulinho4u $
 *         - $Revision: 1.8 $
 *
 */

(function($){

    /**
     *  jQuery definition
     *
     *  @see    http://jquery.com/
     *  @name   jQuery
     *  @class  jQuery Library
     */

    /**
     * jQuery 'fn' definition to anchor all public plugin methods.
     *
     * @see         http://jquery.com/
     * @name        fn
     * @class       jQuery Library public method anchor
     * @memberOf    jQuery
     */

    /**
     *  Namespace for all properties and methods
     *
     *  @namespace   ptTimeSelect
     *  @memberOf    jQuery
     */
    jQuery.ptTimeSelect         = {};
    jQuery.ptTimeSelect.version = "__BUILD_VERSION_NUMBER__";

    /**
     * The default options for all calls to ptTimeSelect. Can be
     * overwriten with each individual call to {@link jQuery.fn.ptTimeSelect}
     *
     * @type {Object} options
     * @memberOf jQuery.ptTimeSelect
     * @see jQuery.fn.ptTimeSelect
     */
    jQuery.ptTimeSelect.options = {
        containerClass: undefined,
        containerWidth: '22em',
        hoursLabel:     'Hour',
        minutesLabel:   'Minutes',
        setButtonLabel: 'Set',
        popupImage:     undefined,
        onFocusDisplay: true,
        zIndex:         10,
        onBeforeShow:   undefined,
        onClose:        undefined
    };

    /**
     * Internal method. Called when page is initialized to add the time
     * selection area to the DOM.
     *
     * @private
     * @memberOf jQuery.ptTimeSelect
     * @return {undefined}
     */
    jQuery.ptTimeSelect._ptTimeSelectInit = function () {
        jQuery(document).ready(
            function () {
                //if the html is not yet created in the document, then do it now
                if (!jQuery('#ptTimeSelectCntr').length) {
                    jQuery("body").append(
                            '<div id="ptTimeSelectCntr" class="">'
                        +    '        <div class="ui-widget ui-widget-content ui-corner-all">'
                        +    '        <div class="ui-widget-header ui-corner-all">'
                        +    '            <div id="ptTimeSelectCloseCntr" style="float: right;">'
                        +    '                <a href="javascript: void(0);" onclick="jQuery.ptTimeSelect.closeCntr();" '
                        +    '                        onmouseover="jQuery(this).removeClass(\'ui-state-default\').addClass(\'ui-state-hover\');" '
                        +    '                        onmouseout="jQuery(this).removeClass(\'ui-state-hover\').addClass(\'ui-state-default\');"'
                        +    '                        class="ui-corner-all ui-state-default">'
                        +    '                    <span class="ui-icon ui-icon-circle-close">X</span>'
                        +    '                </a>'
                        +    '            </div>'
                        +    '            <div id="ptTimeSelectUserTime" style="float: left;">'
                        +    '                <span id="ptTimeSelectUserSelHr">1</span> : '
                        +    '                <span id="ptTimeSelectUserSelMin">00</span> '
                        +    '                <span id="ptTimeSelectUserSelAmPm">AM</span>'
                        +    '            </div>'
                        +    '            <br style="clear: both;" /><div></div>'
                        +    '        </div>'
                        +    '        <div class="ui-widget-content ui-corner-all">'
                        +    '            <div>'
                        +    '                <div class="ptTimeSelectTimeLabelsCntr">'
                        +    '                    <div class="ptTimeSelectLeftPane" style="width: 50%; text-align: center; float: left;" class="">Hour</div>'
                        +    '                    <div class="ptTimeSelectRightPane" style="width: 50%; text-align: center; float: left;">Minutes</div>'
                        +    '                </div>'
                        +    '                <div>'
                        +    '                    <div style="float: left; width: 50%;">'
                        +    '                        <div class="ui-widget-content ptTimeSelectLeftPane">'
                        +    '                            <div class="ptTimeSelectHrAmPmCntr">'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);" '
                        +    '                                        style="display: block; width: 45%; float: left;">AM</a>'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);" '
                        +    '                                        style="display: block; width: 45%; float: left;">PM</a>'
                        +    '                                <br style="clear: left;" /><div></div>'
                        +    '                            </div>'
                        +    '                            <div class="ptTimeSelectHrCntr">'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);">1</a>'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);">2</a>'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);">3</a>'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);">4</a>'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);">5</a>'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);">6</a>'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);">7</a>'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);">8</a>'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);">9</a>'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);">10</a>'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);">11</a>'
                        +    '                                <a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);">12</a>'
                        +    '                                <br style="clear: left;" /><div></div>'
                        +    '                            </div>'
                        +    '                        </div>'
                        +    '                    </div>'
                        +    '                    <div style="width: 50%; float: left;">'
                        +    '                        <div class="ui-widget-content ptTimeSelectRightPane">'
                        +    '                            <div class="ptTimeSelectMinCntr">'
                        +    '                                <a class="ptTimeSelectMin ui-state-default" href="javascript: void(0);">00</a>'
                        +    '                                <a class="ptTimeSelectMin ui-state-default" href="javascript: void(0);">05</a>'
                        +    '                                <a class="ptTimeSelectMin ui-state-default" href="javascript: void(0);">10</a>'
                        +    '                                <a class="ptTimeSelectMin ui-state-default" href="javascript: void(0);">15</a>'
                        +    '                                <a class="ptTimeSelectMin ui-state-default" href="javascript: void(0);">20</a>'
                        +    '                                <a class="ptTimeSelectMin ui-state-default" href="javascript: void(0);">25</a>'
                        +    '                                <a class="ptTimeSelectMin ui-state-default" href="javascript: void(0);">30</a>'
                        +    '                                <a class="ptTimeSelectMin ui-state-default" href="javascript: void(0);">35</a>'
                        +    '                                <a class="ptTimeSelectMin ui-state-default" href="javascript: void(0);">40</a>'
                        +    '                                <a class="ptTimeSelectMin ui-state-default" href="javascript: void(0);">45</a>'
                        +    '                                <a class="ptTimeSelectMin ui-state-default" href="javascript: void(0);">50</a>'
                        +    '                                <a class="ptTimeSelectMin ui-state-default" href="javascript: void(0);">55</a>'
                        +    '                                <br style="clear: left;" /><div></div>'
                        +    '                            </div>'
                        +    '                        </div>'
                        +    '                    </div>'
                        +    '                </div>'
                        +    '            </div>'
                        +    '            <div style="clear: left;"></div>'
                        +    '        </div>'
                        +    '        <div id="ptTimeSelectSetButton">'
                        +    '            <a href="javascript: void(0);" onclick="jQuery.ptTimeSelect.setTime()"'
                        +    '                    onmouseover="jQuery(this).removeClass(\'ui-state-default\').addClass(\'ui-state-hover\');" '
                        +    '                        onmouseout="jQuery(this).removeClass(\'ui-state-hover\').addClass(\'ui-state-default\');"'
                        +    '                        class="ui-corner-all ui-state-default">'
                        +    '                SET'
                        +    '            </a>'
                        +    '            <br style="clear: both;" /><div></div>'
                        +    '        </div>'
                        +    '        <!--[if lte IE 6.5]>'
                        +    '            <iframe style="display:block; position:absolute;top: 0;left:0;z-index:-1;'
                        +    '                filter:Alpha(Opacity=\'0\');width:3000px;height:3000px"></iframe>'
                        +    '        <![endif]-->'
                        +    '    </div></div>'
                    );

                    var e = jQuery('#ptTimeSelectCntr');

                    // Add the events to the functions
                    e.find('.ptTimeSelectMin')
                        .bind("click", function(){
                            jQuery.ptTimeSelect.setMin($(this).text());
                         });

                    e.find('.ptTimeSelectHr')
                        .bind("click", function(){
                            jQuery.ptTimeSelect.setHr($(this).text());
                         });

                    $(document).mousedown(jQuery.ptTimeSelect._doCheckMouseClick);
                }//end if
            }
        );
    }();// jQuery.ptTimeSelectInit()


    /**
     * Sets the hour selected by the user on the popup.
     *
     * @private
     * @param  {Integer}   h   -   Interger indicating the hour. This value
     *                      is the same as the text value displayed on the
     *                      popup under the hour. This value can also be the
     *                      words AM or PM.
     * @return {undefined}
     *
     */
    jQuery.ptTimeSelect.setHr = function(h) {
        if (    h.toLowerCase() == "am"
            ||  h.toLowerCase() == "pm"
        ) {
            jQuery('#ptTimeSelectUserSelAmPm').empty().append(h);
        } else {
            jQuery('#ptTimeSelectUserSelHr').empty().append(h);
        }
    };// END setHr() function

    /**
     * Sets the minutes selected by the user on the popup.
     *
     * @private
     * @param {Integer}    m   - interger indicating the minutes. This
     *          value is the same as the text value displayed on the popup
     *          under the minutes.
     * @return {undefined}
     */
    jQuery.ptTimeSelect.setMin = function(m) {
        jQuery('#ptTimeSelectUserSelMin').empty().append(m);
    };// END setMin() function

    /**
     * Takes the time defined by the user and sets it to the input
     * element that the popup is currently opened for.
     *
     * @private
     * @return {undefined}
     */
    jQuery.ptTimeSelect.setTime = function() {
        var tSel = jQuery('#ptTimeSelectUserSelHr').text()
                    + ":"
                    + jQuery('#ptTimeSelectUserSelMin').text()
                    + " "
                    + jQuery('#ptTimeSelectUserSelAmPm').text();
        jQuery(".isPtTimeSelectActive").val(tSel);
        this.closeCntr();

    };// END setTime() function

    /**
     * Displays the time definition area on the page, right below
     * the input field.  Also sets the custom colors/css on the
     * displayed area to what ever the input element options were
     * set with.
     *
     * @private
     * @param {String} uId - Id of the element for whom the area will
     *                  be displayed. This ID was created when the
     *                  ptTimeSelect() method was called.
     * @return {undefined}
     *
     */
    jQuery.ptTimeSelect.openCntr = function (ele) {
        jQuery.ptTimeSelect.closeCntr();
        jQuery(".isPtTimeSelectActive").removeClass("isPtTimeSelectActive");
        var cntr            = jQuery("#ptTimeSelectCntr");
        var i               = jQuery(ele).eq(0).addClass("isPtTimeSelectActive");
        var opt             = i.data("ptTimeSelectOptions");
        var style           = i.offset();
        style['z-index']    = opt.zIndex;
        style.top           = (style.top + i.outerHeight());
        if (opt.containerWidth) {
            style.width = opt.containerWidth;
        }
        if (opt.containerClass) {
            cntr.addClass(opt.containerClass);
        }
        cntr.css(style);
        var hr    = 1;
        var min   = '00';
        var tm    = 'AM';
        if (i.val()) {
            var re = /([0-9]{1,2}).*:.*([0-9]{2}).*(PM|AM)/i;
            var match = re.exec(i.val());
            if (match) {
                hr    = match[1] || 1;
                min    = match[2] || '00';
                tm    = match[3] || 'AM';
            }
        }
        cntr.find("#ptTimeSelectUserSelHr").empty().append(hr);
        cntr.find("#ptTimeSelectUserSelMin").empty().append(min);
        cntr.find("#ptTimeSelectUserSelAmPm").empty().append(tm);
        cntr.find(".ptTimeSelectTimeLabelsCntr .ptTimeSelectLeftPane")
            .empty().append(opt.hoursLabel);
        cntr.find(".ptTimeSelectTimeLabelsCntr .ptTimeSelectRightPane")
            .empty().append(opt.minutesLabel);
        cntr.find("#ptTimeSelectSetButton a").empty().append(opt.setButtonLabel);
        if (opt.onBeforeShow) {
            opt.onBeforeShow(i, cntr);
        }
        cntr.slideDown("fast");

    };// END openCntr()

    /**
     * Closes (hides it) the popup container.
     * @private
     * @param {Object} i    -   Optional. The input field for which the
     *                          container is being closed.
     * @return {undefined}
     */
    jQuery.ptTimeSelect.closeCntr = function(i) {
        var e = $("#ptTimeSelectCntr");
        if (e.is(":visible") == true) {

            // If IE, then check to make sure it is realy visible
            if (jQuery.support.tbody == false) {
                if (!(e[0].offsetWidth > 0) && !(e[0].offsetHeight > 0) ) {
                    return;
                }
            }

            jQuery('#ptTimeSelectCntr')
                .css("display", "none")
                .removeClass()
                .css("width", "");
            if (!i) {
                i = $(".isPtTimeSelectActive");
            }
            if (i) {
                var opt = i.removeClass("isPtTimeSelectActive")
                            .data("ptTimeSelectOptions");
                if (opt && opt.onClose) {
                    opt.onClose(i);
                }
            }
        }
        return;
    };//end closeCntr()

    /**
     * Closes the timePicker popup if user is not longer focused on the
     * input field or the timepicker
     *
     * @private
     * @param {jQueryEvent} ev -    Event passed in by jQuery
     * @return {undefined}
     */
    jQuery.ptTimeSelect._doCheckMouseClick = function(ev){
        if (!$("#ptTimeSelectCntr:visible").length) {
            return;
        }
        if (   !jQuery(ev.target).closest("#ptTimeSelectCntr").length
            && jQuery(ev.target).not("input.isPtTimeSelectActive").length ){
            jQuery.ptTimeSelect.closeCntr();
        }

    };// jQuery.ptTimeSelect._doCheckMouseClick

    /**
     * FUNCTION: $().ptTimeSelect()
     * Attaches a ptTimeSelect widget to each matched element. Matched
     * elements must be input fields that accept a values (input field).
     * Each element, when focused upon, will display a time selection
     * popoup where the user can define a time.
     *
     * @memberOf jQuery
     *
     * PARAMS:
     *
     * @param {Object}      [opt] - An object with the options for the time selection widget.
     *
     * @param {String}      [opt.containerClass=""] - A class to be associated with the popup widget.
     *
     * @param {String}      [opt.containerWidth=""] - Css width for the container.
     *
     * @param {String}      [opt.hoursLabel="Hours"] - Label for the Hours.
     *
     * @param {String}      [opt.minutesLabel="Minutes"] - Label for the Mintues container.
     *
     * @param {String}      [opt.setButtonLabel="Set"] - Label for the Set button.
     *
     * @param {String}      [opt.popupImage=""] - The html element (ex. img or text) to be appended next to each
     *      input field and that will display the time select widget upon
     *      click.
     *
     * @param {Integer}     [opt.zIndex=10] - Integer for the popup widget z-index.
     *
     * @param {Function}    [opt.onBeforeShow=undefined] - Function to be called before the widget is made visible to the
     *      user. Function is passed 2 arguments: 1) the input field as a
     *      jquery object and 2) the popup widget as a jquery object.
     *
     * @param {Function}    [opt.onClose=undefined] - Function to be called after closing the popup widget. Function
     *      is passed 1 argument: the input field as a jquery object.
     *
     * @param {Bollean}     [opt.onFocusDisplay=true] - True or False indicating if popup is auto displayed upon focus
     *      of the input field.
     *
     *
     * RETURN:
     * @return {jQuery} selection
     *
     *
     *
     * EXAMPLE:
     * @example
     *  $('#fooTime').ptTimeSelect();
     *
     */
    jQuery.fn.ptTimeSelect = function (opt) {
        return this.each(function(){
            if(this.nodeName.toLowerCase() != 'input') return;
            var e = jQuery(this);
            if (e.hasClass('hasPtTimeSelect')){
                return this;
            }
            var thisOpt = {};
            thisOpt = $.extend(thisOpt, jQuery.ptTimeSelect.options, opt);
            e.addClass('hasPtTimeSelect').data("ptTimeSelectOptions", thisOpt);

            //Wrap the input field in a <div> element with
            // a unique id for later referencing.
            if (thisOpt.popupImage || !thisOpt.onFocusDisplay) {
                var img = jQuery('<span>&nbsp;</span><a href="javascript:" onclick="' +
                        'jQuery.ptTimeSelect.openCntr(jQuery(this).data(\'ptTimeSelectEle\'));">' +
                        thisOpt.popupImage + '</a>'
                    )
                    .data("ptTimeSelectEle", e);
                e.after(img);
            }
            if (thisOpt.onFocusDisplay){
                e.focus(function(){
                    jQuery.ptTimeSelect.openCntr(this);
                });
            }
            return this;
        });
    };// End of jQuery.fn.ptTimeSelect

})(jQuery);
