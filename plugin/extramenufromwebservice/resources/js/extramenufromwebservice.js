$(document).ready(function () {
    $('#menu-toggle').click(function() {
        $("#nav-from-webservice").toggle("slow");
        $("#nav-from-webservice").css("z-index", 15);
    });
    $('#menu-toggle').click();
});
