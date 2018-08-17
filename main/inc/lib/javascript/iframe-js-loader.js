$(document).ready(function() {
    //get the path to mediaelement plugins
    var scripts = document.getElementsByTagName('script');
    var scriptPath = scripts[scripts.length-1].src;
    var basePath = scriptPath.substring(0, scriptPath.indexOf('/main/')+1) + 'web/assets/mediaelement/build/';
    $('video:not(.skip), audio:not(.skip)').mediaelementplayer({
        pluginPath: basePath,
        shimScriptAccess: 'always'
        // more configuration
    });
});