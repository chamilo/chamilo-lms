$(document).ready(function() {
    $('.loading_div').hide();
})

function addProgress(id)
{
    $('#loading_div_'+id).show();
    $('#'+id).hide();
}
