
function ajax_sync_setting(wwwroot, settingid) {
    spare = $('#row_'+settingid).html();
    formobj = document.forms['settingsform'];
    url = wwwroot+'plugin/vchamilo/ajax/service.php?what=syncthis&settingid='+settingid+'&value='+encodeURIComponent(formobj.elements['value_'+settingid].value);
    /*if (formobj.elements['del_'+settingid].checked) {
        url += '&del=1';
    }*/

    $('#row_'+settingid).html('<td colspan="7"><img src="'+wwwroot+'plugin/vchamilo/pix/ajax_waiter.gif" /></td>');

    $.get(url, function (data) {
        $('#row_'+settingid).html(spare);
        $('#res_'+settingid).html(data);
    } );
}