/**
 * When included, this snippet prevents contextual menus and keystrokes that
 * make it possible to cut/paste/copy text from the page.
 * This is useful for very secure exams.
 * @author Alberto Torreblanca
 */
$(document).ready(function(){
    $(document).on("cut copy paste contextmenu",function(e) {
        e.preventDefault();
    });
    $(document).keydown(function(e) {
        var forbiddenKeys = new Array('c', 'x', 'v', 'p', 's');
        var keyCode = (e.keyCode) ? e.keyCode : e.which;
        var isCtrl;
        isCtrl = e.ctrlKey
        if (isCtrl) {
            for (i = 0; i < forbiddenKeys.length; i++) {
                if (forbiddenKeys[i] == String.fromCharCode(keyCode).toLowerCase()) {
                    return false;
                }
            }
        }
        return true;
    });
});
