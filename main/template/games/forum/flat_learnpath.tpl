<div class="top-disqus">
    {{ button_reply_to_thread }}
</div>

<div class="forum-disqus">
    {% for post in posts %}
        <div class="post-disqus">
            <div class="col-xs-offset-{{ post.indent_cnt }}">
                <div class="row">
                    <div class="col-xs-1 col-md-2">
                        <div class="thumbnail">
                            {{ post.user_image }}
                        </div>
                    </div>

                    <div class="col-xs-11 col-md-10">
                        <h4 class="username-disqus">
                            {{ post.user_link }}
                            {% if post.parent_link_user %}
                                <span class="reply-post">
                                    <i class="fa fa-share"></i> {{ post.parent_link_user }}
                                </span>
                            {% endif %}
                            <span class="small">•</span>
                            <span class="time timeago" title="{{ post.date }}">{{ post.date }}</span>
                        </h4>
                        <div class="description-disqus">
                            {{ post.post_text }}
                        </div>
                        <div class="tools-disqus">
                            {% if allow_reply %}
                                <a href="reply.php?{{ _p.web_cid_query ~ '&' ~ {'forum': forum.forum_id, 'thread': thread_id, 'post': post.post_id, 'action': 'replymessage'}|url_encode }}" class="btn btn-primary btn-xs reply-disqus">
                                    <i class="fa fa-reply"></i> {{ "Reply"|get_lang }}
                                </a>

                                <a href="reply.php?{{ _p.web_cid_query ~ '&' ~ {'forum': forum.forum_id, 'thread': thread_id, 'post': post.post_id, 'action': 'quote'}|url_encode }}" class="btn btn-success btn-xs quote-disqus">
                                    <i class="fa fa-quote-left"></i> {{ "Quote"|get_lang }}
                                </a>
                            {% endif %}

                            {% if is_allowed_to_session_edit %}
                                {% if not locked %}
                                    <div class="btn-group btn-group-xs" role="toolbar">
                                        <a href="editpost.php?{{ _p.web_cid_query ~ '&' ~ {'forum': forum.forum_id, 'thread': thread_id, 'post': post.post_id, 'edit': 'edition', 'id_attach': ''}|url_encode }}" class="btn btn-default">
                                            <i class="fa fa-pencil"></i>&nbsp;
                                        </a>

                                        <a href="{{ _p.web_self ~ '?' ~ _p.web_cid_query ~ '&' ~ {'forum': forum.forum_id, 'thread': thread_id, 'action': 'delete', 'content': 'post', 'id': post.post_id}|url_encode }}" class="btn btn-default" onclick="javascript:if(!confirm('{{ delete_post_message }}')) return false;">
                                            <i class="fa fa-trash-o"></i>&nbsp;
                                        </a>
                                    </div>
                                {% endif %}
                            {% endif %}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {% endfor %}

    <div  class="load-more-disqus" data-role="more">
        <a id="more-post-disqus" href="#" data-action="more-posts" class="btn btn-default btn-block">{{ "LoadMoreComments"|get_lang }}</a>
    </div>
</div>
