$(document).ready(function() {
    var objects = $(document).find('object');
    var pathname = location.pathname;
    var coursePath = pathname.substr(0, pathname.indexOf('/courses/'));
    var url = "http://"+location.host + coursePath+"/courses/proxy.php?";

    objects.each(function (value, obj) {
        var dialogId = this.id +'_dialog';
        var openerId = this.id +'_opener';
        var link = '<a id="'+openerId+'" href="#">If video does not work, try clicking here.</a>';
        var embed = $("#"+this.id).find('embed').first();

        var height = embed.attr('height');
        var width = embed.attr('width');
        var src = embed.attr('src').replace('https', 'http');

        var completeUrl =  url + 'width='+embed.attr('width')+
            '&height='+height+
            '&id='+this.id+
            '&flashvars='+encodeURIComponent(embed.attr('flashvars'))+
            '&src='+src+
            '&width='+width;

        /*var iframe = '<iframe ' +
            'style="border: 0px;"  width="100%" height="100%" ' +
            'src="'+completeUrl+
            '">' +
            '</iframe>';

        var content = '<div id="'+dialogId+'">' + iframe+'</div>';*/
        var result = $("#"+this.id).find('#'+openerId);

        if (result.length == 0) {
            $("#" + this.id).append('<br />' + link);

            $('#' + openerId).click(function () {
                var window = window.open(completeUrl, "Video", "width=" + width + ", " + "height=" + height + "");
                window.document.title = 'Video';
            });
        }
    });
});
