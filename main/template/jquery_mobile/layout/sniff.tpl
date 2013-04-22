{% raw %}
<script LANGUAGE="JavaScript">
var nav ="";
var screen_size_w;
var screen_size_h;
var java="";
var type_mimetypes="";
var suffixes_mimetypes="";
var list_plugins="";
var check_some_activex="";
var check_some_plugins="";
var java_sun_ver="";

<!-- check Microsoft Internet Explorer -->
if (navigator.userAgent.indexOf("MSIE") != -1) { var nav="ie";}

<!-- check Screen Size -->
screen_size_w=screen.width;
screen_size_h=screen.height;

<!-- list mimetypes types, suffixes and plugins (no for IE) -->
if (nav!="ie"){
        
        if (navigator.mimeTypes && navigator.mimeTypes.length > 0) {
        
                for (i=0; i < navigator.mimeTypes.length; i++) {
                        type_mimetypes=type_mimetypes+" "+navigator.mimeTypes[i].type;
                        suffixes_mimetypes=suffixes_mimetypes+" "+navigator.mimeTypes[i].suffixes;
                        if (navigator.mimeTypes[i].enabledPlugin!=null) {
                                list_plugins=list_plugins+" "+navigator.mimeTypes[i].enabledPlugin.name;
                        }               
                }
        }
}
<!-- check some activex for IE -->
if (nav=="ie"){
        //TODO:check wmediaplayer are too aggressive. Then we can assume that if there Windows, there Wmediaplayer?
        
        var check_some_activex = 
        DetectActiveXObject("ShockwaveFlash.ShockwaveFlash.1", "flash_yes")+
        DetectActiveXObject("QuickTime.QTElementBehavior", "quicktime_yes")+
        //DetectActiveXObject("MediaPlayer.MediaPlayer.1","wmediaplayer_yes")+
        DetectActiveXObject("acroPDF.PDF.1","acrobatreader_yes");
        
        function DetectActiveXObject(ObjectName, name) { 
                result = false;
                        document.write('<SCRIPT LANGUAGE=VBScript\> \n');
                        document.write('on error resume next \n');
                        document.write('result = IsObject(CreateObject("' + ObjectName + '")) \n');
                        document.write('</SCRIPT\> \n');
                if (result) return name+' , '; else return '';
        }
}
<!-- check some plugins for not IE -->
if (nav!="ie"){

        if (list_plugins.indexOf("Shockwave Flash")!=-1){
                check_some_plugins=check_some_plugins+', flash_yes';
        }
        if (list_plugins.indexOf("QuickTime")!=-1){
                check_some_plugins=check_some_plugins+', quicktime_yes';
        }
        if (list_plugins.indexOf("Windows Media Player")!=-1){
                check_some_plugins=check_some_plugins+', wmediaplayer_yes';
        }
        if (list_plugins.indexOf("Adobe Acrobat")!=-1){
                check_some_plugins=check_some_plugins+',acrobatreader_yes';
        }
}
<!-- java -->
if(navigator.javaEnabled()==true){java="java_yes";}else{java="java_no";}

<!-- check java Sun ver -->
//for not IE
if (nav!="ie"){
        if (navigator.mimeTypes["application/x-java-applet"]){ java_sun_ver="javasun_yes";}
        if (navigator.mimeTypes["application/x-java-applet;jpi-version=1.6.0_24"]){ java_sun_ver=java_sun_ver+" , javasun_ver_1.6_24_yes"; }//This java version 1.6.0_24 is problematic, the user should be updated

}
//for IE
if (nav=="ie"){
        //1.5->end nov 2009
        //TODO:extract minor version
        var java_sun_ver =
        DetectActiveXObject("JavaWebStart.isInstalled","javasun_yes")+
        DetectActiveXObject("JavaWebStart.isInstalled.1.4.2.0","javasun_ver_1.4_yes")+
        DetectActiveXObject("JavaWebStart.isInstalled.1.5.0.0","javasun_ver_1.5_yes")+
        DetectActiveXObject("JavaWebStart.isInstalled.1.6.0.0","javasun_ver_1.6_yes")+
        DetectActiveXObject("JavaWebStart.isInstalled.1.7.0.0","javasun_ver_1.7_yes");
        
        function DetectActiveXObject(ObjectName, name) { 
                result = false;
                        document.write('<SCRIPT LANGUAGE=VBScript\> \n');
                        document.write('on error resume next \n');
                        document.write('result = IsObject(CreateObject("' + ObjectName + '")) \n');
                        document.write('</SCRIPT\> \n');
                if (result) return name+' , '; else return '';
        }
}

<!-- Send to server -->
function sendSniff(){
        document.forms.sniff_nav_form.sniff_navigator.value="checked";
        document.forms.sniff_nav_form.sniff_navigator_screen_size_w.value=screen_size_w;
        document.forms.sniff_nav_form.sniff_navigator_screen_size_h.value=screen_size_h;
        document.forms.sniff_nav_form.sniff_navigator_type_mimetypes.value=type_mimetypes;
        document.forms.sniff_nav_form.sniff_navigator_suffixes_mimetypes.value=suffixes_mimetypes;
        document.forms.sniff_nav_form.sniff_navigator_list_plugins.value=list_plugins;
        document.forms.sniff_nav_form.sniff_navigator_check_some_activex.value=check_some_activex;
        document.forms.sniff_nav_form.sniff_navigator_check_some_plugins.value=check_some_plugins;
        document.forms.sniff_nav_form.sniff_navigator_java.value=java;
        document.forms.sniff_nav_form.sniff_navigator_java_sun_ver.value=java_sun_ver;
        document.sniff_nav_form.submit(); 
}
</script>
{% endraw %}

<form name="sniff_nav_form" method="POST">
<input type="hidden" name="sniff_navigator">
<input type="hidden" name="sniff_navigator_screen_size_w">
<input type="hidden" name="sniff_navigator_screen_size_h">
<input type="hidden" name="sniff_navigator_type_mimetypes">
<input type="hidden" name="sniff_navigator_suffixes_mimetypes">
<input type="hidden" name="sniff_navigator_list_plugins">
<input type="hidden" name="sniff_navigator_check_some_activex">
<input type="hidden" name="sniff_navigator_check_some_plugins">
<input type="hidden" name="sniff_navigator_java">
<input type="hidden" name="sniff_navigator_java_sun_ver">
</form>
{#
	<script>
    	sendSniff();
	</script>
#}