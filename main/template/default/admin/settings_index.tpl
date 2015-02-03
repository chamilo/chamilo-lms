<script>
$(document).ready(function() {
    $.ajax({
        url:'{{ web_admin_ajax_url }}?a=version',
        success:function(version){
            $(".admin-block-version").html(version);
        }
    });

    $('.edit-block a').on('click', function(e) {
        e.preventDefault();

        var $self = $(this);

        $('#extra-block').val($self.data('id'));
        $('#modal-extra-title').text($self.data('label'));

        $('#modal-extra').modal('show');
    });

    $('#btn-block-editor-save').on('click', function(e) {
        e.preventDefault();

        var save = $.ajax('{{ _p.web_ajax }}admin.ajax.php', {
            type: 'post',
            data: $('#block-extra-data').serialize() + '&a=save_block_extra'
        });

        $.when(save).done(function() {
            window.location.reload();
        });
    });
});
</script>

<div id="settings">
    <div class="row">
    {% for block_item in blocks %}
        <div id="tabs-{{ loop.index }}" class="span6">
            <div class="well_border {{ block_item.class }}">
                {% if block_item.editable and _u.status == 1 %}
                    <div class="pull-right edit-block" id="edit-{{ block_item.class }}">
                        <a href="#" data-label="{{ block_item.label }}" data-id="{{ block_item.class }}">
                            <img src="{{ _p.web_img }}icons/22/edit.png" alt="{{ 'Edit' | get_lang }}" title="{{ 'Edit' | get_lang }}">
                        </a>
                    </div>
                {% endif %}
                <h4>{{ block_item.icon }} {{ block_item.label }}</h4>
                <div style="list-style-type:none">
                    {{ block_item.search_form }}
                </div>
                {% if block_item.items is not null %}
                    <ul>
    		    	{% for url in block_item.items %}
    		    		<li>
                            <a href="{{ url.url }}">
                                {{ url.label }}
                            </a>
                        </li>
    				{% endfor %}
                    </ul>
                {% endif %}

                {% if block_item.extra is not null %}
                    <div>
                    {{ block_item.extra }}
                    </div>
                {% endif %}
            </div>
        </div>
    {% endfor %}
    </div>
</div>

<div id="modal-extra" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modal-extra-title" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3 id="modal-extra-title">{{ 'Blocks' | get_lang }}</h3>
    </div>
    <div class="modal-body">
        <form action="#" method="post" id="block-extra-data">
            <textarea rows="5" name="content" class="input-block-level" id="extra-content"></textarea>
            <input type="hidden" name="block" id="extra-block" value="">
        </form>
    </div>
    <div class="modal-footer">
        <a href="#" id="btn-block-editor-save" class="btn btn-primary">{{ 'Save' | get_lang }}</a>
    </div>
</div>
