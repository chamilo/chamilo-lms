$(document).ready(function () {
    $('#menu-toggle').click(function() {
        $("#nav-from-webservice").toggle("slow");
        $("#nav-from-webservice").css("z-index", 15);
    });
// Uncomment following line to set the menu open by default
//    $('#menu-toggle').click();
});
