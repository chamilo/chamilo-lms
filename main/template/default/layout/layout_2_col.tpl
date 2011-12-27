{extends file="default/layout/main.tpl"}

{block name=header}
	{include file="default/layout/header.tpl"}
{/block}

{block name=body}	
	{if $show_sniff == 1 }
	 	{include file="default/layout/sniff.tpl"}
	{/if}
	<div id="maincontent" class="maincontent">
		
		{$plugin_courses_block}
		{$home_page_block}
		{$sniff_notification}
		{$message}
		{$content}
		{$announcements_block}

                {if !(empty($hot_courses)) }	
                     <h3>{"HottestCourses"|get_lang}</h3>
                    {foreach $hot_courses as $hot_course}						
                        <div class="categories-block-course">
                            <div class="categories-content-course">
                                <h4>{$hot_course.extra_info.name}</h4>								
								{"Visits"|get_lang} : {$hot_course.course_count}
								
								{if ($hot_course.extra_info.visibility == 3)} 
									<a class="ajax a_button white small" title="" href="{'WEB_AJAX_PATH'|get_path}course_home.ajax.php?a=show_course_information&code={$hot_course.course_code}">
										{"Description"|get_lang}
									</a>
								{/if}								
								
								{* World *}
								{if ($hot_course.extra_info.visibility == 3)}
								 <a class="a_button gray small" title="" href="{'WEB_COURSE_PATH'|get_path}{$hot_course.extra_info.path}/index.php">
										{"GoToCourse"|get_lang}
									</a>
								{/if}								
                            </div>
                        </div>
                    {/foreach}
                {/if}
	</div>
	
	<div id="menu-wrapper">
	    
		{if $_u.logged == 0}
			{include file="default/layout/login_form.tpl"}
		{/if}

		{$profile_block}			
		{$account_block}		
		{$teacher_block}
		{$notice_block}
		{$navigation_course_links}
		{$plugin_courses_right_block}
		{$reservation_block}
		{$search_block}
		{$classes_block}
		{$skills_block}
	</div>
{/block}

{block name=footer}
	{include file="default/layout/footer.tpl"}	
{/block}