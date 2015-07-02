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

    var forumPagination = {
        currentPage: 1,
        loadPreviousPosts: function(callback) {
            var getPrevPost = $.ajax('{{ _p.web_ajax }}forum.ajax.php?{{ _p.web_cid_query }}', {
                type: 'post',
                dataType: 'json',
                data: {
                    a: 'get_more_posts',
                    forum: parseInt({{ forum.forum_id }}),
                    thread: parseInt({{ thread_id }}),
                    page: parseInt(forumPagination.currentPage),
                    locked: {{ locked ? 'true': 'false' }}
                },
                success: function() {
                    forumPagination.currentPage++;
                }
            });

            $.when(getPrevPost).done(function(response) {
                if (response.error) {
                    return;
                }

                $.each(response.posts, function() {
                    var post = this;

                    if (post.parentId > 0) {
                        $('[data-post="' + post.parentId +'"] > .post-disqus').append(post.html);
                        $(".timeago").timeago();
                    } else {
                        $(post.html).insertBefore('#form-reply-to-post-0');
                    }

                    if (callback) {
                        callback();
                    }
                });
            });
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
        forumPagination.loadPreviousPosts(function() {
            resizeContainer();
        });

        CKEDITOR.on('instanceReady', function() {
            resizeContainer();
        });

        $('.top-disqus, .forum-disqus').on('click', '.btn-reply-post', function(e) {
            e.preventDefault();

            var parentPostId = $(this).data('parent-post') || 0;

            replyForm.parentPostId = parseInt(parentPostId);
            replyForm.show('reply');
        });

        $('.forum-disqus').on('click', '.btn-quote-post', function(e) {
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

        $('#more-post-disqus').on('click', function(e) {
            e.preventDefault();

            forumPagination.loadPreviousPosts(function() {
                resizeContainer();
            });
        });
    });
</script>

<div class="top-disqus">
    {{ button_reply_to_thread }}
</div>

<div class="forum-disqus">
    <div id="form-reply-to-post-0"></div>
    {% for post_data in posts %}
        {% include template ~ '/forum/flat_learnpath_post.tpl' %}
    {% endfor %}

    <div  class="load-more-disqus" data-role="more">
        <a id="more-post-disqus" href="#" data-action="more-posts" class="btn btn-default btn-block">{{ "LoadMoreComments"|get_lang }}</a>
    </div>
</div>
