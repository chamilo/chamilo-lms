
$(document).ready(function () {
    $("#emailTitle").focus();
});

$(function() {
    $('.resizable').resizable();
    $('.resizable-vertical').resizable({
        handles: "n, s"
    });
});

var Announcement = {};

Announcement.sort = function(c_id, ids, f){
    var url = www + '/main/inc/ajax/announcement.ajax.php';
    var data = {c_id: c_id, ids: ids, action: 'sort'};
    $.post(url, data, f);
};

Announcement.hide = function(c_id, id, token, f)
{
    var url = www + '/main/inc/ajax/announcement.ajax.php';
    var data = {c_id: c_id, id: id, action: 'hide', sec_token: token};
    $.post(url, data, f, 'json');
};

Announcement.show = function(c_id, id, token, f)
{
    var url = www + '/main/inc/ajax/announcement.ajax.php';
    var data = {c_id: c_id, id: id, action: 'show', sec_token: token};
    $.post(url, data, f, 'json');
};

Announcement.del = function(c_id, id, token, f)
{
    var url = www + '/main/inc/ajax/announcement.ajax.php';
    var data = {c_id: c_id, id: id, action: 'delete', sec_token: token};
    $.post(url, data, f, 'json');
};

Announcement.delete_by_course = function(c_id, token, f)
{
    var url = www + '/main/inc/ajax/announcement.ajax.php';
    var data = {c_id: c_id, action: 'delete_by_course', sec_token: token};
    $.post(url, data, f, 'json');
};

Announcement.delete_all = function(c_id, ids, token, f)
{
    var url = www + '/main/inc/ajax/announcement.ajax.php';
    var data = {c_id: c_id, ids: ids, action: 'delete_all', sec_token: token};
    $.post(url, data, f, 'json');
};



function move_selected_option(from, to){
    var selected = $("option:selected", from)
    selected.each(function(index, option)
    {
        option = $(option);
        option.detach();
        $(to).append(option);
    });
}

function update_hidden_field(name){
    
    var select = $('#' + name + '_selected');
    var options = $("option", select)
    //update hidden field
    var keys = [];
    options.each(function(index, option)
    {
        option = $(option);
        keys.push(option.val());
    });
    keys = keys.join(',');
    
    var hidden = $('#' + name);
    hidden.val(keys);
}


function toggle_list_selector(name)
{
    var list = $('#' + name + '_list');
    var overview = $('#' + name + '_overview');
    if(list.css('display') == 'none'){
        list.show();
        overview.hide();
    }
    else
    {
        list.hide();
        overview.show();
    }
    
    var select = $('#' + name + '_selected');
    
    //update overview
    var content = [];
    var options = $("option", select)
    options.each(function(index, option)
    {
        option = $(option);
        content.push(option.text());
    });
    
    content = content.join(', ');
    content = (content == '') ? lang.Everybody : content;
    overview.text(content);
}


function toggle_sendto()
{
    var list = $('#recipient_list');
    var overview = $('#recipient_overview');
    if(list.css('display') == 'none'){
        list.show();
        overview.hide();
    }
    else
    {
        list.hide();
        overview.show();
    }
    
    var selected = $('#selectedform');
    var content = list_box_content(selected[0])
    content = (content == '') ? lang.Everybody : content;
    overview.text(content);
}

function list_box_content(box)
{
    if(box.options.length == 0)
    {
        return '';
    }
    var values = [];
    var i;
    for (i = 0; i < box.options.length; i++) {
        values[i] = box.options[i].text;
    }
    return values.join(', ');
}

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