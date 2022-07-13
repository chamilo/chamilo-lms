{% set admin_chamilo_announcements_disable = 'admin_chamilo_announcements_disable'|api_get_configuration_value %}

<script>
    $(document).ready(function () {
    
        setTimeout(function(){
            $.ajax({
                url: '{{ web_admin_ajax_url }}?a=version',
                success: function (version) {
                    $(".admin-block-version").html(version);
                }
            });
        }, 3000);

        {% if _u.is_admin %}
            (function (CKEDITOR) {
                CKEDITOR.replace('extra_content');
    
                var extraContentEditor = CKEDITOR.instances.extra_content;
    
                $('button.admin-edit-block').on('click', function (e) {
                    e.preventDefault();
    
                    var $self = $(this);
    
                    var extraContent = $.ajax('{{ _p.web_ajax }}admin.ajax.php', {
                        type: 'post',
                        data: {
                            a: 'get_extra_content',
                            block: $self.data('id')
                        }
                    });
    
                    $.when(extraContent).done(function (content) {
                        extraContentEditor.setData(content);
                        $('#extra-block').val($self.data('id'));
                        $('#modal-extra-title').text($self.data('label'));
    
                        $('#modal-extra').modal('show');
                    });
                });
            })(window.CKEDITOR);

            {% if not admin_chamilo_announcements_disable %}
                $
                    .ajax('{{ web_admin_ajax_url }}?a=get_latest_news')
                    .then(function (response) {
                        if (!response.length) {
                            return;
                        }

                        $('#chamilo-news').show(150);
                        $('#chamilo-news-content').html(response);
                    });
            {% endif %}
        {% endif %}
    });
</script>

<section id="settings" class="row">
    {% set columns = 2 %}
    {% for block_item in blocks %}
        {% if block_item.items %}
            <div id="tabs-{{ loop.index }}" class="settings-block col-md-6">
                <div class="panel panel-default {{ block_item.class }}">
                    <div class="panel-heading">
                        {{ block_item.icon }} {{ block_item.label }}
                        {% if block_item.editable and _u.is_admin %}
                            <button type="button" class="btn btn-link btn-sm admin-edit-block pull-right"
                                    data-label="{{ block_item.label }}" data-id="{{ block_item.class }}">
                                <img src="{{ "edit.png"|icon(22) }}" width="22" height="22" alt="{{ "Edit"|get_lang }}"
                                     title="{{ "Edit"|get_lang }}"/>
                            </button>
                        {% endif %}
                    </div>
                    <div class="panel-body">
                        <div style="display: block;">
                            {{ block_item.search_form }}
                        </div>
                        {% if block_item.items is not null %}
                            <div class="block-items-admin">
                                <ul class="list-items-admin">
                                    {% for url in block_item.items %}
                                        <li{% if url.class %} class="{{ url.class }}"{% endif %}>
                                            <a href="{{ url.url }}">
                                                {{ url.label }}
                                            </a>
                                        </li>
                                    {% endfor %}
                                </ul>
                            </div>
                        {% endif %}

                        {% if block_item.extra is not null %}
                            <div>
                                {{ block_item.extra }}
                            </div>
                        {% endif %}

                        {% if block_item.extraContent %}
                            <div>{{ block_item.extraContent }}</div>
                        {% endif %}
                    </div>
                </div>
            </div>
        {% endif %}
    {% endfor %}
</section>

{% if not admin_chamilo_announcements_disable %}
    <section id="chamilo-news" style="display: none;">
        <div class="alert alert-info">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <div id="chamilo-news-content"></div>
        </div>
    </section>
{% endif %}

{% if _u.is_admin %}
    <div class="modal fade" id="modal-extra">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ 'Close'|get_lang }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="modal-extra-title">{{ 'Blocks'|get_lang }}</h4>
                </div>
                <div class="modal-body">
                    {{ extraDataForm }}
                </div>
            </div>
        </div>
    </div>
{% endif %}
