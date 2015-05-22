{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
    <script>
        var url = '{{ _p.web_ajax }}sequence.ajax.php';
        var parentList = [];
        var resourceId = 0;
        var sequenceId = 0;

        $(document).ready(function() {
            var type = $('input[name="sequence_type"]').val();

            $('button[name="set_requirement"]').prop('disabled', true);

            $('#parents').on('click', 'a', function() {
                var vertexId = $(this).attr('data-id');
                var parent = $(this).parent();

                if (vertexId) {
                    $.ajax({
                        url: url + '?a=delete_vertex&id='+resourceId+'&vertex_id=' + vertexId + '&type=' +type+'&sequence_id='+sequenceId,
                        success: function (data) {
                            parent.remove();
                        }
                    });
                }
            });

            $('button[name="use_as_reference"]').click(function() {
                $('button[name="set_requirement"]').prop('disabled', false);

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
                            listLoaded.forEach(function(value) {
                                $.ajax({
                                    url: url + '?a=get_icon&id='+ value+'&type='+type+'&sequence_id='+sequenceId+'&show_delete=1',
                                    success:function(data){
                                        $('#parents').append(data);
                                        parentList.push(id);
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

                return false;
            });

            $('button[name="set_requirement"]').click(function() {
                $("#item option:selected" ).each(function() {
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

            $('button[name="save_resource"]').click(function() {
                if (resourceId != 0) {
                    var params = decodeURIComponent(parentList);
                    $.ajax({
                        url: url + '?a=save_resource&id=' + resourceId + '&parents=' + params+'&type='+type+'&sequence_id='+sequenceId,
                        success: function (data) {
                            alert('saved');
                        }
                    });
                }
                return false;
            });
        });
    </script>

    <div class="row">
        <div class="col-md-3">
            {{ left_block }}
        </div>
        <div class="col-md-9">
            <h3>
                {{ 'ItemsTheReferenceDependsOn' | get_lang }}
            </h3>
            <div id="parents">
            </div>

            <h3>{{ 'Item' | get_lang }}</h3>
            <div id="resource">
            </div>

            <h3>{{ 'Dependencies' | get_lang }}</h3>
            <div id="children">
            </div>
            {{ right_block }}
        </div>
    </div>
{% endblock %}
