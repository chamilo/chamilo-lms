<div class="media" data-post="{{ post_data.post.id }}">
    <div class="media-left">
        <div class="thumbnail">
            {{ post_data.user.image }}
        </div>
    </div>

    <div class="media-body">
        <h4 class="media-heading">
            {{ post_data.user.link }}
            <span class="small">•</span>
            <span class="time timeago" title="{{ post_data.post.date }}">{{ post_data.post.date }}</span>
        </h4>

        <div>
            {{ post_data.post.text }}
            <div>
                {% if allow_reply %}
                    <a data-parent-post="{{ post_data.post.id }}" href="#" class="btn btn-primary btn-xs reply-disqus btn-reply-post">
                        <i class="fa fa-reply"></i> {{ "Reply"|get_lang }}
                    </a>

                    <a data-parent-post="{{ post_data.post.id }}" href="#" class="btn btn-success btn-xs quote-disqus btn-quote-post">
                        <i class="fa fa-quote-left"></i> {{ "Quote"|get_lang }}
                    </a>
                {% endif %}

                {% if is_allowed_to_session_edit %}
                    {% if not locked %}
                        <div class="btn-group btn-group-xs" role="toolbar">
                            <a href="editpost_data.post.php?{{ _p.web_cid_query ~ '&' ~ {'forum': forum.forum_id, 'thread': thread_id, 'post': post_data.post.id, 'edit': 'edition', 'id_attach': ''}|url_encode }}" class="btn btn-default">
                                <i class="fa fa-pencil"></i>&nbsp;
                            </a>

                            <a href="{{ _p.web_self ~ '?' ~ _p.web_cid_query ~ '&' ~ {'forum': forum.forum_id, 'thread': thread_id, 'action': 'delete', 'content': 'post', 'id': post_data.post.id}|url_encode }}" class="btn btn-default" onclick="javascript:if (!confirm('{{ delete_confirm_message }}')) return false;">
                                <i class="fa fa-trash-o"></i>&nbsp;
                            </a>
                        </div>
                    {% endif %}
                {% endif %}
            </div>
        </div>

        <div id="form-reply-to-post-{{ post_data.post.id }}"></div>
    </div>
</div>
