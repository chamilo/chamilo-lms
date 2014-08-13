/**
 * Created with JetBrains PhpStorm.
 * User: albert1t0
 * Date: 24/04/13
 * Time: 04:33 PM
 * This is a little script to prevent Cut Copy Paste Actions in Browser.
 * At this time for exercise form.
 */

$(document).ready(function(){
    $(document).live("cut copy paste contextmenu",function(e) {
        e.preventDefault();
    });
    $(document).keydown(function(e) {
        var forbiddenKeys = new Array('c', 'x', 'v');
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
