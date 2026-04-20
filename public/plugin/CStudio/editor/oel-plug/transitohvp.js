
function encodeTxtToHVP(src){
    src = src.replace(/!br!/g,"<br/>");
    src = src.replace(/!slash47!/g,'&#47;');
    src = src.replace(/!slash92!/g,'&#92;');
    src = src.replace(/!spe44!/g,',');
    src = src.replace(/!aro!/g,'@');
    src = src.replace(/!pourc!/g,'%');
    src = src.replace(/!djez!/g,'#');
    return src;
}

