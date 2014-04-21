<script type="text/javascript" src="{{www}}/main/glossary/resources/js/proxy.js"></script>
<script type="text/javascript" src="{{www}}/main/glossary/resources/js/ui.js"></script>
<script type="text/javascript" src="{{www}}/main/glossary/resources/js/jquery.dataTables.js"></script>

<script type="text/javascript">
    
$(function() {

    ui.proxy = glossary;

    window.context = {};
    
    context.sec_token = '{{sec_token}}';
    context.c_id = '{{c_id}}';
    context.session_id = '{{session_id}}';
    context.www = '{{www}}';
    context.ajax = '{{www}}/main/inc/ajax/glossary.ajax.php';
    
    if(typeof(lang) == "undefined")
    {
        window.lang = {};
    }
    
    lang.ConfirmYourChoice = "{{'ConfirmYourChoice'|get_lang}}";  
    
});

</script>