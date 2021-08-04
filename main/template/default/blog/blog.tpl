<div class="row">
    <div class="col-md-3">
        <div class="sidebar">
            <div id="calendar-blog" class="panel panel-default">
                <div class="panel-heading">
                    {{ 'Calendar'|get_lang }}
                </div>
                <div class="panel-body">
                    {{ calendar }}
                </div>
            </div>
            <div id="search-blog" class="panel panel-default">
                <div class="panel-heading">
                    {{ 'Search'|get_lang }}
                </div>
                <div class="panel-body">
                    <form action="blog.php?{{ _p.web_cid_query }}" method="get" enctype="multipart/form-data">
                        <div class="form-group">
                            <input type="hidden" name="cidReq" value="{{ course_code }}"/>
                            <input type="hidden" name="id_session" value="{{ session_id }}"/>
                            <input type="hidden" name="blog_id" value="{{ id_blog }}"/>
                            <input type="hidden" name="action" value="view_search_result"/>
                            <input type="text" class="form-control" size="20" name="q" value="{{ search | remove_xss }}"/>
                        </div>
                        <button class="btn btn-default btn-block" type="submit">
                            <em class="fa fa-search"></em> {{ 'Search'|get_lang }}
                        </button>
                    </form>
                </div>
            </div>
            <div id="task-blog" class="panel panel-default">
                <div class="panel-heading">
                    {{ 'MyTasks'|get_lang }}
                </div>
                <div class="panel-body">
                    {{ task }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="blog">
            <div id="blog-header">
                {% if not search %}
                    <div class="title">
                        <h1>{{ title }}</h1>
                    </div>
                    <div class="description">
                        {{ description }}
                    </div>
                {% else %}
                    <div class="title">
                        <h1>{{ search }}</h1>
                    </div>
                {% endif %}
            </div>
            <div id="list-articles">
                {% for item in articles %}
                    <article id="post-{{ item.id_post }}" class="article-post">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <h3 class="title-post">
                                    <a href="{{ _p.web_self ~ '?' ~ _p.web_cid_query ~ '&' ~ {'action':'view_post', 'blog_id':item.id_blog, 'post_id':item.id_post}|url_encode }}#add_comment"
                                       title="{{ item.title }}">
                                        {{ item.title | remove_xss }}
                                    </a>
                                </h3>
                                <div class="info-post">
                                    <span class="date"><i class="fa fa-clock-o"></i> {{ item.post_date }}</span>
                                    <span class='comments'>
                                        <i class="fa fa-comment-o"></i> {{ item.n_comments }} {{ 'Comments' | get_lang }}
                                    </span>
                                    <span class="autor"><i class="fa fa-user"></i> {{ item.autor }}</span>
                                    <span class="score"><i class="fa fa-star"></i> {{ item.score_ranking }}</span>
                                </div>

                                {% if item.extract %}
                                    <div id="post-extract-{{ item.id_post }}" class="show">
                                        <p>
                                            {{ item.extract }}
                                            <button type="button" class="btn btn-link btn-read-more" data-id="{{ item.id_post }}">
                                                {{ 'ReadMore'|get_lang }}
                                            </button>
                                        </p>
                                    </div>
                                {% endif %}

                                <div id="post-content-{{ item.id_post }}" class="{{ item.extract ? 'hidden' : '' }}">
                                    {{ item.content | remove_xss }}
                                </div>

                                {% if item.files %}
                                    <div class="well well-sm files">
                                        <i class="fa fa-paperclip" aria-hidden="true"></i>
                                        <a href="download.php?file={{ item.files.path }}">
                                            {{ item.files.filename | remove_xss }}
                                        </a>
                                    </div>
                                {% endif %}
                            </div>
                        </div>
                    </article>
                {% endfor %}
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {
        $('.btn-read-more').on('click', function (e) {
            e.preventDefault();

            var postId = $(this).data('id') || 0;

            if (!postId) {
                return;
            }

            $('#post-extract-' + postId).removeClass('show').addClass('hidden');
            $('#post-content-' + postId).removeClass('hidden').addClass('show');
        });
    });
</script>
