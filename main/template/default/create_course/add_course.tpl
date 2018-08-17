{% if just_created == 1%}
    <h3>{{ 'JustCreated'|get_lang }} {{ course_title }}</h3>
    <hr />
{% endif %}

<h3>{{ 'ThingsToDo'|get_lang }}</h3>
<br />
<div id="course_thing_to_do" class="row">
    <div class="col-md-3">
        <div class="thumbnail">
            {{ 'info.png' | img(64, 'AddCourseDescription'|get_lang ) }}
            <div class="caption">
                <a href="{{ _p.web_main }}course_description/index.php?cidReq={{ course_id }}" class="btn btn-default">
                    <em class="fa fa-info-circle"></em> {{'AddCourseDescription'|get_lang}}
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="thumbnail">
            {{ 'folder_document.png' | img(64, 'UploadADocument'|get_lang ) }}
            <div class="caption">
                <a href="{{ _p.web_main }}document/document.php?cidReq={{ course_id }}" class="btn btn-default">
                    <em class="fa fa-paper-plane"></em> {{'UploadADocument'|get_lang}}
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="thumbnail">
            {{ 'forum.png' | img(64, 'AddForum'|get_lang ) }}

            <div class="caption">
                <a href="{{ _p.web_main }}forum/index.php?cidReq={{ course_id }}" class="btn btn-default">
                    <em class="fa fa-users"></em> {{ 'AddForum'|get_lang }}
                </a>
            </div>
        </div>
    </div>
    {% if ("allow_user_course_subscription_by_course_admin" | api_get_setting) == 'true' or _u.is_admin == 1 %}
    <div class="col-md-3">
        <div class="thumbnail">
        {{ 'user.png' | img(64, 'SubscribeUserToCourse'|get_lang ) }}
            <div class="caption">
            <a href="{{ _p.web_main }}user/subscribe_user.php?cidReq={{ course_id }}" class="btn btn-default">
                <em class="fa fa-user-plus"></em> {{ 'SubscribeUserToCourse'|get_lang }}
            </a>
            </div>
        </div>
    </div>
    {% endif %}
</div>
<div class="clear"></div>
