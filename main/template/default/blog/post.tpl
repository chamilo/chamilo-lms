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
                            <div class="btn-group" role="group" aria-label="...">
                                <a class="btn btn-default" href="blog.php?action=edit_post&blog_id={{ post.id_blog }}&post_id={{ post.id_post }}&article_id={{ post.id_post }}&task_id={{ post.id_task }}" title="{{ 'EditThisPost' | get_lang }}">
                                {{ 'edit.png' |img }}
                                </a>
                                <a class="btn btn-default" href="blog.php?action=view_post&blog_id={{ post.id_blog }}&post_id={{ post.id_post }}&do=delete_article&article_id={{ post.id_post }}&task_id={{ post.id_task }}" 
                                   title="{{ 'DeleteThisArticle' | get_lang}}" 
                                   onclick="javascript:if(!confirm('{{ 'ConfirmYourChoice' | get_lang }}')) return false;" >
                                    {{ 'delete.png' |img }}
                                </a>
                            </div>
                            
                        </div>
                        <div class="title">
                            <h1 title="{{ post.title }}">{{ post.title }}</h1>
                        </div>
                        <div class="info-post">
                            <span class="date"><i class="fa fa-clock-o"></i> {{ post.post_date }}</span> 
                            <span class='comments'><i class="fa fa-comment-o"></i> {{ post.n_comments }} {{ 'Comments' | get_lang }} </span>
                            <span class="autor"><i class="fa fa-user"></i> {{ post.autor }}</span>
                        </div>
                        <div class="content-post">
                                {{ post.content }}
                        </div>
                        {% if post.files  %}
                            <div class="files">
                                <i class="fa fa-paperclip" aria-hidden="true"></i> <a href="download.php?file={{ post.files.path }}">{{ post.files.filename }}</a>
                            </div>
                        {% endif %}
                        <div class="comments-post">
                            <h3>{{ post.n_comments }} {{ 'Comments' | get_lang }} </h3>
                            {{ post.comments_html }}
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