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
    window.open("./calendar_view.php", "calendar", "width=220,height=200,status=no");
	day = eval("document." + form + "." + field + "day.options["+ "document." + form + "." + field + "day.selectedIndex].value");
    month = eval("document." + form + "." + field + "month.options["+ "document." + form + "." + field + "month.selectedIndex].value");
   	month = month-1;
    year = eval("document." + form + "." + field + "year.options["+ "document." + form + "." + field + "year.selectedIndex].value");
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
        str += "<td" + style + "><a href=\"javascript:returnDate(" + i +","+month+","+year + ");\">" + i + "</a></td>"
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
	cmd = "window.opener.document."+window.opener.formName+"."+window.opener.fieldName+"day.selectedIndex = "+(d-1);
	eval(cmd);
	cmd = "window.opener.document."+window.opener.formName+"."+window.opener.fieldName+"month.selectedIndex = "+m;
	eval(cmd);
	date = new Date();
	year = date.getFullYear()-1;
	cmd = "window.opener.document."+window.opener.formName+"."+window.opener.fieldName+"year.selectedIndex = "+(y-year);
	eval(cmd);
    window.close();
}




function mktime() {
                        
                var no, ma = 0, mb = 0, i = 0, d = new Date(), argv = arguments, argc = argv.length;
                d.setHours(0,0,0); d.setDate(1); d.setMonth(1); d.setYear(1972);
             
                var dateManip = {
                    0: function(tt){ return d.setHours(tt); },
                    1: function(tt){ return d.setMinutes(tt); },
                    2: function(tt){ set = d.setSeconds(tt); mb = d.getDate() - 1; return set; },
                    3: function(tt){ set = d.setMonth(parseInt(tt)-1); ma = d.getFullYear() - 1972; return set; },
                    4: function(tt){ return d.setDate(tt+mb); },
                    5: function(tt){ return d.setYear(tt+ma); }
                };
                
                for( i = 0; i < argc; i++ ){
                    no = parseInt(argv[i]*1);
                    if (isNaN(no)) {
                        return false;
                    } else {
                        // arg is number, lets manipulate date object
                        if(!dateManip[i](no)){
                            // failed
                            return false;
                        }
                    }
                }
             
                return Math.floor(d.getTime()/1000);
}   

function validate_date(){

                var fday = document.new_calendar_item.fday.value;
                var fmonth = document.new_calendar_item.fmonth.value;
                var fyear = document.new_calendar_item.fyear.value;     
                var fhour = document.new_calendar_item.fhour.value;     
                var fminute = document.new_calendar_item.fminute.value;
                var fdate = mktime(fhour,fminute,0,fmonth,fday,fyear)
                        
                var end_fday = document.new_calendar_item.end_fday.value;
                var end_fmonth = document.new_calendar_item.end_fmonth.value;
                var end_fyear = document.new_calendar_item.end_fyear.value;     
                var end_fhour = document.new_calendar_item.end_fhour.value;     
                var end_fminute = document.new_calendar_item.end_fminute.value;
                var end_fdate = mktime(end_fhour,end_fminute,0,end_fmonth,end_fday,end_fyear)       
                
                var title = document.new_calendar_item.title.value;
                
                msg_id1 = document.getElementById(\"msg_error1\");
                msg_id2 = document.getElementById(\"msg_error2\");      
                msg_id3 = document.getElementById(\"msg_error3\");
       
                
                
                if(title==\"\"){
                     
                    msg_id1.style.display =\"block\";
                    msg_id1.innerHTML=".get_lang('FieldRequired').";
                    msg_id2.innerHTML=\"\"; msg_id3.innerHTML=\"\";   
                }
                else if(fdate > end_fdate)
                {
                     
                    msg_id2.style.display =\"block\";
                    msg_id2.innerHTML=".get_lang('DateExpiredNotBeLessDeadLine').";
                    msg_id1.innerHTML=\"\"; msg_id3.innerHTML=\"\";                                             
                }
                else if (checkDate(ends_month,ends_day,ends_year) == false)
                {
                    msg_id3.style.display =\"block\";
                    msg_id3.innerHTML="'.get_lang('InvalidDate').'";
                    msg_id1.innerHTML=""; msg_id2.innerHTML="";      
                }         
                
                        
}
