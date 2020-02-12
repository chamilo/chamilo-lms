{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
    {{ tabs }}
    <script>
        var url = '{{ _p.web_ajax }}sequence.ajax.php?type={{ sequence_type }}';
        var parentList = [];
        var resourceId = 0;
        var sequenceId = 0;

        function useAsReference(sequenceId, itemId) {
            var id = itemId || $("#item option:selected").val();
            sequenceId = $("#sequence_id option:selected").val();

            // Cleaning parent list.
            parentList = [];

            // Check if data exists and load parents
            $.ajax({
                url: url + '&a=load_resource&load_resource_type=parent&id=' + id + '&sequence_id='+sequenceId,
                success: function (data) {
                    if (data) {
                        var loadingResources = new Array(),
                            listLoaded = data.split(',');

                        listLoaded.forEach(function(value) {
                            var loadResource = $.ajax(url, {
                                data: {
                                    a: 'get_icon',
                                    id: value,
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
                                        $('#parents').append('<em class="fa fa-plus fa-3x sequence-plus-icon"></em>');
                                    }
                                }
                            });
                        }
                    }
                }
            });

            // Check if data exists and load children
            $.ajax({
                url: url + '&a=load_resource&load_resource_type=children&id=' + id + '&sequence_id='+sequenceId,
                success: function (data) {
                    if (data) {
                        var listLoaded = data.split(',');
                        listLoaded.forEach(function(value) {
                            $.ajax({
                                url: url + '&a=get_icon&id='+ value+'&sequence_id='+sequenceId,
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
                url: url + '&a=get_icon&id='+ id+'&sequence_id='+sequenceId,
                success:function(data){
                    $('#resource').html(data);
                    parentList.push(id);
                    resourceId = id;
                }
            });

            $.ajax({
                url: url + '&a=graph&sequence_id='+sequenceId,
                success: function (data) {
                    $('#show_graph').html(data);
                }
            });
        }

        $(function() {
            // By default "set requirement" is set to false
            sequenceId = $("#sequence_id option:selected" ).val();

            // Load parents
            $('#parents').on('click', 'a.delete_vertex, a.undo_delete', function(e) {
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

            $('#parents, #resource, #children').on('click', '.parent .sequence-id', function(e) {
                e.preventDefault();

                var itemId = $(this).parents('.parent').data('id') || 0;

                if (!itemId) {
                    return;
                }

                $('button[name="set_requirement"]').prop('disabled', false);
                $('#requirements').prop('disabled', false);
                $('button[name="save_resource"]').prop('disabled', false);

                useAsReference(sequenceId, itemId);
            });

            // Button use as reference
            $('button[name="use_as_reference"]').click(function(e) {
                e.preventDefault();

                if (!sequenceId) {
                    return;
                }

                $('#pnl-preview').show();
                $('button[name="set_requirement"], #requirements, button[name="save_resource"]').prop('disabled', false);
                $('#requirements').selectpicker('refresh');

                useAsReference(sequenceId);
            });

            // Button set requirement
            $('button[name="set_requirement"]').click(function(e) {
                e.preventDefault();

                var requirementsSelectedEl = $("#requirements option:selected");

                if (0 === requirementsSelectedEl.length || !sequenceId) {
                    return;
                }

                requirementsSelectedEl.each(function() {
                    var id = $(this).val();
                    if ($.inArray(id, parentList) == -1) {
                        $.ajax({
                            url: url + '&a=get_icon&id=' + id + '&sequence_id='+sequenceId,
                            success: function (data) {
                                $('#parents').append(data);
                                parentList.push(id);
                            }
                        });
                    }
                });
            });

            // Button save
            $('button[name="save_resource"]').click(function(e) {
                e.preventDefault();

                if (!sequenceId) {
                    return;
                }

                var self = $(this).prop('disabled', true);

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

                        var savingResource = $.ajax(url, {
                            data: {
                                a: 'save_resource',
                                id: resourceId,
                                parents: params,
                                sequence_id: sequenceId
                            }
                        });

                        $.when(savingResource).done(function(response) {
                            $('#global-modal')
                                    .find('.modal-dialog')
                                    .removeClass('modal-lg')
                                    .addClass('modal-sm');
                            $('#global-modal')
                                    .find('.modal-body')
                                    .html(response);
                            $('#global-modal').modal('show');

                            self.prop('disabled', false);

                            useAsReference(sequenceId);
                        });
                    }
                });
            });

            $('select#sequence_id').on('change', function() {
                sequenceId = $(this).val();

                if (sequenceId > 0) {
                    $('#sequence-title').text(
                        $(this).children(':selected').text()
                    );
                    $('#sequence_name').text(
                        $(this).children(':selected').text()
                    );
                    $('#show_graph').html('');
                    $('#pnl-configuration').show();
                    $('#pnl-preview').hide();
                    $('button[name="set_requirement"], #requirements, button[name="save_resource"]').prop('disabled', true);
                    $('#item, button[name="use_as_reference"]').prop('disabled', false);

                    return;
                }

                $('#pnl-configuration').hide();
            });

            $('form[name="frm_select_delete"]').on('submit', function (e) {
                var confirmDelete = confirm('{{ 'AreYouSureToDeleteJS'|get_lang }}');

                if (!confirmDelete) {
                    e.preventDefault();
                }
            });

            $('select#sequence_id').trigger('change');
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

    <div id="pnl-configuration" class="panel panel-default" style="display: none;">
        <div class="panel-body">
            <div class="section-title-sequence">{{ 'SequenceConfiguration' | get_lang }}: <b><span id="sequence_name"></span></b></div>
            <div class="row">
                {{ configure_sequence }}
            </div>

        </div>
    </div>
    <div id="pnl-preview" class="panel panel-default" style="display: none;">
        <div class="panel-body">
            <div class="section-title-sequence">{{ 'SequencePreview' | get_lang }} &mdash;
                <span id="sequence-title"></span>
            </div>
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
