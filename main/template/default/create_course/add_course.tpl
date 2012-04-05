{{just_created_link}}
<h3>{{'JustCreated'|get_lang}} {{course_title}}</h3>
    <hr />
<h3>{{'ThingsToDo'|get_lang}}</h3>
<ul class="welcome_course">
    <li>     
        <p><img src="{{_p.web_img}}icons/64/home.png"/></p>
        <a href="{{course_url}}" class="btn">
            {{'CourseHomepage'|get_lang}}
        </a>    
    </li>    
    <li>     
        <p><img src="{{_p.web_img}}icons/64/user.png"/></p>
        <a href="{{_p.web_main}}user/subscribe_user.php?cidReq={{course_id}}" class="btn">
            {{'SubscribeUserToCourse'|get_lang}}
        </a>    
    </li>
    <li>              
       <p><img src="{{_p.web_img}}icons/64/info.png"/></p>
        <a href="{{_p.web_main}}course_description/?cidReq={{course_id}}" class="btn">
            {{'AddCourseDescription'|get_lang}}
        </a>
    </li>
    <li>              
        <p><img src="{{_p.web_img}}icons/64/reference.png"/></p>
        <a href="{{_p.web_main}}course_info/infocours.php?cidReq={{course_id}}" class="btn">
            {{'ModifInfo'|get_lang}}
        </a>
    </li>
</ul>
    
<div class="clear">
</div>
<br />