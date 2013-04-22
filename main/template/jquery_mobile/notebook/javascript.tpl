<script type="text/javascript" src="{{www}}/main/notebook/resources/js/proxy.js"></script>
<script type="text/javascript" src="{{www}}/main/notebook/resources/js/ui.js"></script>

<script type="text/javascript">
    
$(function() {

    ui.proxy = notebook;

    window.context = {};
    
    context.sec_token = '{{sec_token}}';
    context.c_id = '{{c_id}}';
    context.session_id = '{{session_id}}';
    context.www = '{{www}}';
    context.ajax = '{{www}}/main/inc/ajax/notebook.ajax.php';
    
    if(typeof(lang) == "undefined")
    {
        window.lang = {};
    }
    
    lang.ConfirmYourChoice = "{{'ConfirmYourChoice'|get_lang}}";  
    
});

</script>