{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
    <script>
        var url = '{{ _p.web_ajax }}sequence.ajax.php';
        var parentList = [];
        var resourceId = 0;
        var sequenceId = 0;

        function useAsReference(type, sequenceId) {
            var id = $("#item option:selected" ).val();

            sequenceId = $("#sequence_id option:selected" ).val();

            // Cleaning parent list.
            parentList = [];

            // Check if data exists and load parents
            $.ajax({
                url: url + '?a=load_resource&load_resource_type=parent&id=' + id + '&type='+type+'&sequence_id='+sequenceId,
                success: function (data) {
                    if (data) {
                        var listLoaded = data.split(',');
                        var count = listLoaded.length;
                        listLoaded.forEach(function(value, index) {
                            $.ajax({
                                url: url + '?a=get_icon&id='+ value+'&type='+type+'&sequence_id='+sequenceId+'&show_delete=1',
                                success:function(data){
                                    $('#parents').append(data);
                                    if (index != (count - 1)) {
                                        $('#parents').append('<div class="sequence-plus-icon">+</div>');
                                    }
                                    parentList.push(value);
                                }
                            });
                        });
                    }
                }
            });

            // Check if data exists and load children
            $.ajax({
                url: url + '?a=load_resource&load_resource_type=children&id=' + id + '&type='+type+'&sequence_id='+sequenceId,
                success: function (data) {
                    if (data) {
                        var listLoaded = data.split(',');
                        listLoaded.forEach(function(value) {
                            $.ajax({
                                url: url + '?a=get_icon&id='+ value+'&type='+type+'&sequence_id='+sequenceId,
                                success:function(data){
                                    $('#children').append(data);
                                }
                            });
                        });
                    }
                }
            });

            // Cleaning
            $('#parents').html('');
            $('#children').html('');

            $.ajax({
                url: url + '?a=get_icon&id='+ id+'&type='+type+'&sequence_id='+sequenceId,
                success:function(data){
                    $('#resource').html(data);
                    parentList.push(id);
                    resourceId = id;
                }
            });

            $.ajax({
                url: url + '?a=graph&type='+type+'&sequence_id='+sequenceId,
                success: function (data) {
                    $('#show_graph').html(data);
                }
            });
        }

        $(document).ready(function() {
            var type = $('input[name="sequence_type"]').val();
            // By default "set requirement" is set to false

            $('button[name="set_requirement"]').prop('disabled', true);
            $('#requirements').prop('disabled', true);
            $('button[name="save_resource"]').prop('disabled', true);

            sequenceId = $("#sequence_id option:selected" ).val();

            // Load parents
            $('#parents').on('click', 'a', function() {
                var vertexId = $(this).attr('data-id');
                var parent = $(this).parent();

                if (vertexId) {
                    var class_click = $(this).attr('class');

                    if (class_click == 'undo_delete')  {
                        parent.find('span').css('text-decoration', 'none');
                        parent.find('.undo_delete').remove();
                    } else {
                        parent.parent().find('span').css('text-decoration', 'line-through');

                        var link = "<a href=\"javascript:void(0);\" class=\"undo_delete\" data-id="+vertexId+">{{ 'Undo' | get_lang }}</a>";
                        parent.parent().append(link);
                    }
                }
            });

            // Button use as reference

            $('button[name="use_as_reference"]').click(function() {
                $('button[name="set_requirement"]').prop('disabled', false);
                $('#requirements').prop('disabled', false);
                $('button[name="save_resource"]').prop('disabled', false);

                useAsReference(type, sequenceId);

                return false;
            });

            // Button set requirement

            $('button[name="set_requirement"]').click(function() {
                $("#requirements option:selected" ).each(function() {
                    var id = $(this).val();
                    if ($.inArray(id, parentList) == -1) {
                        $.ajax({
                            url: url + '?a=get_icon&id=' + id + '&type='+type+'&sequence_id='+sequenceId,
                            success: function (data) {
                                $('#parents').append(data);
                                parentList.push(id);
                            }
                        });
                    }
                });
                return false;
            });

            // Button save
            $('button[name="save_resource"]').click(function() {

                // Delete all vertex confirmed to be deleted.
                $('#parents .delete_vertex').each( function (index, data) {
                    var vertexId = $(this).attr('data-id');
                    var textDecoration = $(this).parent().css('text-decoration');
                    if (textDecoration == 'line-through') {
                        $.ajax({
                            async:false,
                            url: url + '?a=delete_vertex&id=' + resourceId + '&vertex_id=' + vertexId + '&type=' + type + '&sequence_id=' + sequenceId,
                            success: function (data) {
                                parentList.splice( $.inArray(vertexId, parentList), 1 );
                                /*parent.remove();
                                useAsReference(type, sequenceId);*/
                            }
                        });
                    }
                });

                if (resourceId != 0) {
                    var params = decodeURIComponent(parentList);
                    $.ajax({
                        url: url + '?a=save_resource&id=' + resourceId + '&parents=' + params+'&type='+type+'&sequence_id='+sequenceId,
                        success: function (data) {
                            alert('saved');
                            useAsReference(type, sequenceId);
                        }
                    });
                }
                return false;
            });
        });
    </script>
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="section-title-sequence">{{ 'SequenceSelection' | get_lang }}</div>
            <div class="row">
                <div class="col-md-6">
                    {{ create_sequence }}
                </div>
                <div class="col-md-6">
                    {{ select_sequence }}
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-body">
            <div class="section-title-sequence">{{ 'SequenceConfiguration' | get_lang }}</div>
            <div class="row">

                {{ configure_sequence }}
            </div>

        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="section-title-sequence">{{ 'SequencePreview' | get_lang }}</div>
            <div class="row">
                <div class="col-md-9">
                    <h4 class="title-sequence">
                        {{ 'ItemsTheReferenceDependsOn' | get_lang }}
                    </h4>
                    <div id="parents">
                    </div>
                    <div class="arrow-sequence"></div>
                    <h4 class="title-sequence">{{ 'Item' | get_lang }}</h4>
                    <div id="resource">
                    </div>
                    <div class="arrow-sequence"></div>
                    <h4 class="title-sequence">{{ 'Dependencies' | get_lang }}</h4>
                    <div id="children">
                    </div>

                </div>
                <div class="col-md-3">
                    <h4 class="title-sequence">{{ 'GraphDependencyTree' | get_lang }}</h4>
                    <div id="show_graph"></div>
                </div>

            </div>
            {{ save_sequence }}
        </div>
    </div>
{% endblock %}
