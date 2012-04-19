// Begin javascript menu swapper

function move(fbox, tbox) {
    "use strict";
    var arrFbox = [];
    var arrTbox = [];
    var arrLookup = [];

    var i;
    for (i = 0; i < tbox.options.length; i++) {
        arrLookup[tbox.options[i].text] = tbox.options[i].value;
        arrTbox[i] = tbox.options[i].text;
    }

    var fLength = 0;
    var tLength = arrTbox.length;

    for (i = 0; i < fbox.options.length; i++)
    {
        arrLookup[fbox.options[i].text] = fbox.options[i].value;

        if (fbox.options[i].selected && fbox.options[i].value != "")
        {
            arrTbox[tLength] = fbox.options[i].text;
            tLength++;
        } 
        else 
        {
            arrFbox[fLength] = fbox.options[i].text;
            fLength++;
        }
    }
    
    arrFbox.sort();
    arrTbox.sort();
    
    var arrFboxGroup = [];
    var arrFboxUser = [];
    var prefix_x;
    
    var x;
    for (x = 0; x < arrFbox.length; x++) {
        prefix_x = arrFbox[x].substring(0, 2);
        if (prefix_x == 'G:') {
            arrFboxGroup.push(arrFbox[x]);
        } else {
            arrFboxUser.push(arrFbox[x]);
        }
    }
    
    arrFboxGroup.sort();
    arrFboxUser.sort();
    arrFbox = arrFboxGroup.concat(arrFboxUser);
    
    var arrTboxGroup = [];
    var arrTboxUser = [];
    var prefix_y;
    
    var y;
    for (y = 0; y < arrTbox.length; y++) {
        prefix_y = arrTbox[y].substring(0, 2);
        if (prefix_y == 'G:') {
            arrTboxGroup.push(arrTbox[y]);
        } else {
            arrTboxUser.push(arrTbox[y]);
        }
    }
    
    arrTboxGroup.sort();
    arrTboxUser.sort();
    arrTbox = arrTboxGroup.concat(arrTboxUser);
    
    fbox.length = 0;
    tbox.length = 0;
    
    var c;
    for (c = 0; c < arrFbox.length; c++) 
    {
        var no = new Option();
        no.value = arrLookup[arrFbox[c]];
        no.text = arrFbox[c];
        fbox[c] = no;
    }
    for (c = 0; c < arrTbox.length; c++) 
    {
        var no = new Option();
        no.value = arrLookup[arrTbox[c]];
        no.text = arrTbox[c];
        tbox[c] = no;
    }
}

function validate() 
{
    "use strict";
    var f = document.new_calendar_item;
    f.submit();
    return true;
}


function selectAll(cbList, bSelect, showwarning) {
    "use strict";
    
    if (document.getElementById('emailTitle').value == '') {
        document.getElementById('msg_error').innerHTML = lang.FieldRequired;
        document.getElementById('msg_error').style.display = 'block';
        document.getElementById('emailTitle').focus();
    } else {
        //if (cbList.length < 1) {
        //if (!confirm(lang.Send2All)) {
        //	return false;
        //}
        //}
        var i;
        for (i = 0; i < cbList.length; i++)
        {
            cbList[i].selected = cbList[i].checked = bSelect;
        }
        document.f1.submit();
    }
}

function reverseAll(cbList) 
{
    "use strict";
    var i;
    for (i = 0; i < cbList.length; i++) 
    {
        cbList[i].checked = !(cbList[i].checked);
        cbList[i].selected = !(cbList[i].selected);
    }
}


function plus_attachment() {
    "use strict";
    if (document.getElementById('options').style.display == 'none') {
        document.getElementById('options').style.display = 'block';
        document.getElementById('plus').innerHTML = '&nbsp;<img style="vertical-align:middle;" src="../img/div_hide.gif" alt="" />&nbsp;' + lang.AddAnAttachment;
    } else {
        document.getElementById('options').style.display = 'none';
        document.getElementById('plus').innerHTML = '&nbsp;<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;' + lang.AddAnAttachment;
    }
}
// End