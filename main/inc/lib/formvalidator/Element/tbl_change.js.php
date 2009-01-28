<?php
// $Id: tbl_change.js.php 18057 2009-01-28 21:07:19Z cfasanando $
require ('../../../global.inc.php');
?>
var day;
var month;
var year;
var hour;
var minute;
var second;
var clock_set = 0;

/**
 * Opens calendar window.
 *
 * @param   string      form name
 * @param   string      field name
 */
function openCalendar(form, field) {
 	formblock= document.getElementById(form);
	forminputs = formblock.getElementsByTagName('select');
	var datevalues = new Array();
	var dateindex = 0;
	for (i = 0; i < forminputs.length; i++) {
		// regex here to check name attribute
		var regex = new RegExp(field, "i");
		if (regex.test(forminputs[i].getAttribute('name'))) {
			datevalues[dateindex++] = forminputs[i].value;
		}
	}
    window.open("<?php echo api_get_path(WEB_CODE_PATH); ?>inc/lib/formvalidator/Element/calendar_popup.php", "calendar", "width=220,height=200,status=no");
	day = datevalues[0];
	month = datevalues[1];
	year = datevalues[2];
	month--;
	formName = form;
	fieldName =field;
}

/**
 * Formats number to two digits.
 *
 * @param   int number to format.
 */
function formatNum2(i, valtype) {
    f = (i < 10 ? '0' : '') + i;
    if (valtype && valtype != '') {
        switch(valtype) {
            case 'month':
                f = (f > 12 ? 12 : f);
                break;

            case 'day':
                f = (f > 31 ? 31 : f);
                break;
        }
    }

    return f;
}

/**
 * Formats number to four digits.
 *
 * @param   int number to format.
 */
function formatNum4(i) {
    return (i < 1000 ? i < 100 ? i < 10 ? '000' : '00' : '0' : '') + i;
}

/**
 * Initializes calendar window.
 */
function initCalendar() {
    if (!year && !month && !day) {
		day = window.opener.day;
		month = window.opener.month;
		year  = window.opener.year;
        if (isNaN(year) || isNaN(month) || isNaN(day) || day == 0) {
            dt      = new Date();
            year    = dt.getFullYear();
            month   = dt.getMonth();
            day     = dt.getDate();
        }
    } else {
        /* Moving in calendar */
        if (month > 11) {
            month = 0;
            year++;
        }
        if (month < 0) {
            month = 11;
            year--;
        }
    }

    if (document.getElementById) {
        cnt = document.getElementById("calendar_data");
    } else if (document.all) {
        cnt = document.all["calendar_data"];
    }

    cnt.innerHTML = "";

    str = ""

    //heading table
    str += '<table class="calendar"><tr><th class="monthyear" width="50%">';
    str += '<a href="javascript:month--; initCalendar();">&laquo;</a> ';
    str += month_names[month];
    str += ' <a href="javascript:month++; initCalendar();">&raquo;</a>';
    str += '</th><th class="monthyear" width="50%">';
    str += '<a href="javascript:year--; initCalendar();">&laquo;</a> ';
    str += year;
    str += ' <a href="javascript:year++; initCalendar();">&raquo;</a>';
    str += '</th></tr></table>';

    str += '<table class="calendar"><tr>';
    for (i = 0; i < 7; i++) {
        str += "<th  class='daynames'>" + day_names[i] + "</th>";
    }
    str += "</tr>";

    var firstDay = new Date(year, month, 1).getDay();
    var lastDay = new Date(year, month + 1, 0).getDate();

    str += "<tr>";

    dayInWeek = 0;
    for (i = 0; i < firstDay; i++) {
        str += "<td>&nbsp;</td>";
        dayInWeek++;
    }
    for (i = 1; i <= lastDay; i++) {
        if (dayInWeek == 7) {
            str += "</tr><tr>";
            dayInWeek = 0;
        }

        dispmonth = 1 + month;
        actVal = formatNum4(year) + "-" + formatNum2(dispmonth, 'month') + "-" + formatNum2(i, 'day');
        if (i == day) {
            style = ' class="selected"';
        } else {
            style = '';
        }
        str += "<td" + style + "><a href=\"javascript:returnDate(" + i +","+month+","+year + ");\" >" + i + "</a></td>"
        dayInWeek++;
    }
    for (i = dayInWeek; i < 7; i++) {
        str += "<td>&nbsp;</td>";
    }

    str += "</tr></table>";

    cnt.innerHTML = str;    
}

/**
 * Returns date from calendar.
 *
 * @param   string     date text
 */
function returnDate(d,m,y) {
 	formblock= window.opener.document.getElementById(window.opener.formName);
	forminputs = formblock.getElementsByTagName('select');
	var datevalues = new Array();
	var dateindex = 0;
	for (i = 0; i < forminputs.length; i++) {
		// regex here to check name attribute
		var regex = new RegExp(window.opener.fieldName, "i");
		if (regex.test(forminputs[i].getAttribute('name'))) {
			datevalues[dateindex++] = forminputs[i];
			window.close();
		}
	}
	datevalues[0].selectedIndex = (d-1) ;
	datevalues[1].selectedIndex = m;
	date = new Date();
	year = date.getFullYear()-1;
	datevalues[2].selectedIndex = (y-year);

	for(i = 0; i<= 3; i++)
	{
		attributes = datevalues[i].attributes;
		for (attr=0; attr<attributes.length; attr++)
  			if(attributes[attr].nodeName == 'onchange')
  			{
  				datevalues[i].onchange();
  			}
	}
    window.close();
}
