{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
<script>

    function setSearchSelect(columnName) {
        $("#questions").jqGrid('setColProp', columnName, {

           /*searchoptions:{
                dataInit:function(el){
                    $("option[value='1']",el).attr("selected", "selected");
                    setTimeout(function(){
                        $(el).trigger('change');
                    }, 1000);
                }
            }*/
        });
    }

    var added_cols = [];
    var original_cols = [];

    function clean_cols(grid, added_cols) {
        //Cleaning
        for (key in added_cols) {
            //console.log('hide: ' + key);
            grid.hideCol(key);
        };
        grid.showCol('name');
        grid.showCol('display_start_date');
        grid.showCol('display_end_date');
        grid.showCol('course_title');
    }

    function show_cols(grid, added_cols) {
        grid.showCol('name').trigger('reloadGrid');
        for (key in added_cols) {
            //console.log('show: ' + key);
            grid.showCol(key);
        };
    }

    var second_filters = [];

    $(function () {
        {{ js }}

        setSearchSelect("status");

        var grid = $("#questions"),
        prmSearch = {
            multipleSearch : true,
            overlay : false,
            width: 'auto',
            caption: '{{ 'Search' | get_lang }}',
            formclass:'data_table',
            onSearch : function() {
                var postdata = grid.jqGrid('getGridParam', 'postData');

                if (postdata && postdata.filters) {
                    filters = jQuery.parseJSON(postdata.filters);
                    clean_cols(grid, added_cols);
                    added_cols = [];
                    $.each(filters, function(key, value) {
                        /*console.log('key: ' + key );
                        console.log('value: ' + value );*/

                        if (key == 'rules') {
                            $.each(value, function(subkey, subvalue) {
                                if (subvalue.data == undefined) {
                                    return;
                                }
                                if (subvalue.data && subvalue.data == -1) {
                                    return;
                                }

                                /*console.log('subkey: ' + subkey );
                                console.log('subvalue: ' + subvalue );
                                console.log(subvalue);
                                console.log('subvalue.field: ' + subvalue );
                                console.log(subvalue.field);*/

                                //if (added_cols[value.field] == undefined) {
                                    added_cols[subvalue.field] = subvalue.field;
                                //}
                                //grid.showCol(value.field);
                            });
                        }
                    });
                    show_cols(grid, added_cols);
                }
           },
           onReset: function() {
                clean_cols(grid, added_cols);
           }
        };

        original_cols = grid.jqGrid('getGridParam', 'colModel');

        grid.jqGrid('navGrid','#questions_pager',
            { edit:false, add:false, del:false },
            { height:280, reloadAfterSubmit:false }, // edit options
            { height:280, reloadAfterSubmit:false }, // add options
            { reloadAfterSubmit:false },// del options
            prmSearch
        );

        // create the searching dialog
        grid.searchGrid(prmSearch);

        // Fixes search table.
        var searchDialogAll = $("#fbox_"+grid[0].id);
        searchDialogAll.addClass("table");
        var searchDialog = $("#searchmodfbox_"+grid[0].id);
        searchDialog.addClass("ui-jqgrid ui-widget ui-widget-content ui-corner-all");
        searchDialog.css({position:"relative", "z-index":"auto", "float":"left"})
        var gbox = $("#gbox_"+grid[0].id);

        gbox.before(searchDialog);
        gbox.css({clear:"left"});

        $("#searchmodfbox_questions").after('<div id="result" style="float: left;position: relative; width: 100%;"></div>');

        //Select first elements by default
        $('.input-elm').each(function(){
            $(this).find('option:first').attr('selected', 'selected');
        });

        $('.delete-rule').each(function(){
            $(this).click(function(){
                 $('.input-elm').each(function(){
                    $(this).find('option:first').attr('selected', 'selected');
                });
            });
        });
    });

    function ajaxAction(obj) {
        var url = $(obj).attr('data-url');
        $.ajax({
            type: "POST",
            url: url,
            success: function(data) {
                $("#result").html(data);
            }
        });
        event.preventDefault();
        return false;
    }


</script>

<div class="questions">
    {{ grid }}
</div>


{% endblock %}
