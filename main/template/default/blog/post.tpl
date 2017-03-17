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
                    <form action="blog.php" method="get" enctype="multipart/form-data">
                    <div class="form-group">
                        <input type="hidden" name="blog_id" value="{{ id_blog }}" />
                        <input type="hidden" name="action" value="view_search_result" />
                        <input type="text" class="form-control" size="20" name="q" value="{{ search }}" />
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
        <div class="blog" id="post-{{ post.id_post }}">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div id="blog-header">
                        <div id="post-action" class="pull-right">
                            <div class="btn-group btn-group-sm" role="group" aria-label="...">
                                {{ post.actions }}
                            </div>
                        </div>
                        <div class="title">
                            <h1 title="{{ post.title }}">{{ post.title }}</h1>
                        </div>
                        <div class="info-post">
                            <span class="date"><i class="fa fa-clock-o"></i> {{ post.post_date }}</span> 
                            <span class='comments'><i class="fa fa-comment-o"></i> {{ post.n_comments }} {{ 'Comments' | get_lang }} </span>
                            <span class="autor"><i class="fa fa-user"></i>
                                <a href="{{ _p.web }}main/social/profile.php?u={{ post.id_author }}">
                                {{ post.author }}
                                </a>
                            </span>
                        </div>
                        <div class="content-post">
                            {{ post.content }}
                        </div>
                        {% if post.files  %}
                            <div class="well well-sm files">
                                <i class="fa fa-paperclip" aria-hidden="true"></i> <a href="download.php?file={{ post.files.path }}">{{ post.files.filename }}</a>
                            </div>
                        {% endif %}
                        <div class="comments-post">
                            <h3 class="title">{{ post.n_comments }} {{ 'Comments' | get_lang }} </h3>
                            <div id="list-comments">  
                                {% for item in post.comments %}
                                <div class="media">
                                    <div class="media-left">
                                      <a href="{{ _p.web }}main/social/profile.php?u={{ item.id_author }}">
                                        <img class="media-object thumbnail avatar" src="{{ item.info_user.dir }}{{ item.info_user.file }}" alt="{{ item.name_author }}">
                                      </a>
                                    </div>
                                    <div class="media-body">
                                        <div class="pull-right">
                                            {{ item.actions }}
                                        </div>
                                        <h4 class="media-heading">{{ item.title }}</h4>
                                        <div class="info-post">
                                            <span class="date"><i class="fa fa-clock-o"></i> {{ item.comment_date }}</span> 
                                            <span class="autor"><i class="fa fa-user"></i>
                                                <a href="{{ _p.web }}main/social/profile.php?u={{ item.id_author }}">
                                                    {{ item.name_author }}
                                                </a>
                                            </span>
                                            <span class="score"><i class="fa fa-star" aria-hidden="true"></i> {{ item.score_ranking  }}</span>
                                        </div>
                                        {{ item.content }}
                                      
                                        {% if item.files  %}
                                            <div class="well well-sm files">
                                                <i class="fa fa-paperclip" aria-hidden="true"></i> <a href="download.php?file={{ item.files.path }}">{{ item.files.filename }}</a>
                                                <p>{{ item.files.comment }}</p>
                                            </div>
                                        {% endif %}
                                      
                                      <div class="ranking">
                                          {{ item.form_ranking }} 
                                      </div>
                                    </div>
                                </div>
                                {% endfor %}
                            </div>
                        </div>
                        <div class="form-post">
                            {{ post.form_html }}
                        </div>
                    </div>
                </div>
            </div>
        
        </div>
    </div>
</div>