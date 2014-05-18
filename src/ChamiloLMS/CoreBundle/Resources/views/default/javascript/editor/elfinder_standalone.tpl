<!-- elFinder CSS (REQUIRED) -->
<link rel="stylesheet" type="text/css" media="screen" href="{{ _p.web_lib }}elfinder/css/elfinder.min.css">
<link rel="stylesheet" type="text/css" media="screen" href="{{ _p.web_lib }}elfinder/css/theme.css">

<!-- elFinder JS (REQUIRED) -->
<script type="text/javascript" src="{{ _p.web_lib }}elfinder/js/elfinder.min.js"></script>

<!-- elFinder translation (OPTIONAL) -->
<script type="text/javascript" src="{{ _p.web_lib }}elfinder/js/i18n/elfinder.ru.js"></script>

<script type="text/javascript" charset="utf-8">
    $().ready(function() {
        $('#elfinder').elfinder({
            url : '{{ url('editor.controller:connectorAction', {driver_list : driver_list }) }}',
            resizable: false
        }).elfinder('instance');
    });
</script>
<div id="elfinder"></div>
