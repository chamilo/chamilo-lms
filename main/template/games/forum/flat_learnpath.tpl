<script>
    var replyForm = {
        parentPostId: 0,
        show: function(action) {
            $('#form_reply').remove();

            var getForm = $.getJSON('{{ _p.web_ajax }}forum.ajax.php?{{ _p.web_cid_query }}', {
                a: 'reply_form',
                post: replyForm.parentPostId,
                forum: parseInt({{ forum.forum_id }}),
                thread: parseInt({{ thread_id }}),
                action: action
            });

            $.when(getForm).done(function(response) {
                if (response.error) {
                    return;
                }

                resizeContainer();
                $('#form-reply-to-post-' + replyForm.parentPostId).html(response.form);
            });
        },
        send: function(formData) {
            var sendPost = $.ajax('{{ _p.web_ajax }}forum.ajax.php?{{ _p.web_cid_query }}', {
                type: 'post',
                dataType: 'json',
                data: formData
            });

            return sendPost;
        }
    };

    var resizeContainer = function(reset) {
        var iframeContainer = window.parent.document.getElementById('chamilo-disqus');

        if (reset) {
            $(iframeContainer).height(0);
        }

        $(iframeContainer).height(
            $('body').prop('scrollHeight')
        );
    };

    $(document).on('ready', function() {
        resizeContainer(true);

        CKEDITOR.on('instanceReady', function() {
            resizeContainer();
        });

        $('.btn-reply-post').on('click', function(e) {
            e.preventDefault();

            var parentPostId = $(this).data('parent-post') || 0;

            replyForm.parentPostId = parseInt(parentPostId);
            replyForm.show('reply');
        });

        $('.btn-quote-post').on('click', function(e) {
            e.preventDefault();

            var parentPostId = $(this).data('parent-post') || 0;

            replyForm.parentPostId = parseInt(parentPostId);
            replyForm.show('quote');
        });

        $('body').on('submit', '#form_reply', function(e) {
            e.preventDefault();

            var self = $(this);
            var formData = self.serialize();
            var sendForm = replyForm.send(formData);

            self.find(':submit').prop('disabled', true);

            $.when(sendForm).done(function(response) {
                if (!response.error) {
                    window.location.reload();
                } else {
                    self.find(':submit').prop('disabled', false);
                }
            });
        });
    });
</script>

<div class="top-disqus">
    {{ button_reply_to_thread }}
</div>

<div class="forum-disqus">
    <div id="form-reply-to-post-0"></div>
    {% for post in posts %}
        <div class="post-disqus">
            <div class="col-xs-offset-{{ post.indent_cnt }}" data-post="{{ post.post_id }}">
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
                                <a data-parent-post="{{ post.post_id }}" href="#" class="btn btn-primary btn-xs reply-disqus btn-reply-post">
                                    <i class="fa fa-reply"></i> {{ "Reply"|get_lang }}
                                </a>

                                <a data-parent-post="{{ post.post_id }}" href="#" class="btn btn-success btn-xs quote-disqus btn-quote-post">
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
                <div id="form-reply-to-post-{{ post.post_id }}"></div>
            </div>
        </div>
    {% endfor %}

    <div  class="load-more-disqus" data-role="more">
        <a id="more-post-disqus" href="#" data-action="more-posts" class="btn btn-default btn-block">{{ "LoadMoreComments"|get_lang }}</a>
    </div>
</div>
