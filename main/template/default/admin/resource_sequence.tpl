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
                        var loadingResources = new Array(),
                            listLoaded = data.split(',');

                        listLoaded.forEach(function(value) {
                            var loadResource = $.ajax(url, {
                                data: {
                                    a: 'get_icon',
                                    id: value,
                                    type: type,
                                    sequence_id: sequenceId,
                                    show_delete: 1
                                },
                                success: function() {
                                    parentList.push(value);
                                }
                            });

                            loadingResources.push(loadResource);
                        });

                        if (loadingResources.length) {
                            $.when.apply($, loadingResources).done(function() {
                                if (loadingResources.length === 1) {
                                    $('#parents').append(arguments[0]);

                                    return;
                                }

                                var i;

                                for (i = 0; i < arguments.length; i++) {
                                    $('#parents').append(arguments[i][0]);

                                    if (i !== arguments.length - 1) {
                                        $('#parents').append('<i class="fa fa-plus fa-3x sequence-plus-icon"></i>');
                                    }
                                }
                            });
                        }
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
            $('#parents').on('click', 'a', function(e) {
                e.preventDefault();

                var self = $(this),
                    parent = self.parent(),
                    vertexId = self.attr('data-id') || 0;

                if (!vertexId) {
                    return;
                }

                if (self.is('.delete_vertex')) {
                    self.hide();
                    parent.find('.undo_delete').show();

                    self.parents('.parent').addClass('parent-deleted');
                } else if (self.is('.undo_delete')) {
                    self.hide();
                    parent.find('.delete_vertex').show();

                    self.parents('.parent').removeClass('parent-deleted');
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
            $('button[name="save_resource"]').click(function(e) {
                e.preventDefault();

                // parse to integer the parents IDs
                parentList = parentList.map(function(id) {
                    return parseInt(id);
                });

                var deletingVertex = new Array();

                // Delete all vertex confirmed to be deleted.
                $('#parents .parent.parent-deleted').each(function() {
                    var self = $(this),
                        vertexId = self.data('id') || 0,
                        deleteVertex;

                    deleteVertex = $.ajax(url, {
                        data: {
                            a: 'delete_vertex',
                            id: resourceId,
                            vertex_id: vertexId,
                            type: type,
                            sequence_id: sequenceId
                        },
                        success: function() {
                            parentList.splice($.inArray(vertexId, parentList), 1);
                        }
                    });

                    deletingVertex.push(deleteVertex);
                });

                $.when.apply($, deletingVertex).done(function() {
                    if (resourceId != 0) {
                        var params = decodeURIComponent(parentList);

                        $.ajax(url, {
                            data: {
                                a: 'save_resource',
                                id: resourceId,
                                parents: params,
                                type: type,
                                sequence_id: sequenceId
                            },
                            success: function (data) {
                                alert('saved');
                                useAsReference(type, sequenceId);
                            }
                        });
                    }
                });
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
                    <div class="border-sequence">
                        <div class="arrow-sequence"></div>
                    </div>
                    <h4 class="title-sequence">{{ 'Item' | get_lang }}</h4>
                    <div id="resource">
                    </div>
                    <div class="border-sequence">
                        <div class="arrow-sequence"></div>
                    </div>
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
