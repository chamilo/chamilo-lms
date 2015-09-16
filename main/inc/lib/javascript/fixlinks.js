$(document).ready(function() {
    var objects = $(document).find('object');

    objects.each(function (value, obj) {
        var dialogId = this.id +'_dialog';
        var openerId = this.id +'_opener';
        /*var width = this.width;
        var height = this.height;*/

        $("#"+this.id).append('<br /><a id="'+openerId+'" href="#">If video does not work, try clicking here.</a><div id="'+dialogId+'">' + $("#"+this.id).html() +'</div>');
        $('#'+dialogId).dialog({
            autoOpen: false,
            width : 650,
            height : 420,
        });

        $('#' + openerId).click(function() {
            $('#'+dialogId).dialog("open");
        });
    });
});
