<script type="text/javascript">
$(document).ready( function() {
	
	$('.star-rating li a').live('click', function(event) {	

        var id = $(this).parents('ul').attr('id');
                      
           $('#vote_label2_' + id).html("{'Loading'|get_lang}");
           
           $.ajax({
               url: $(this).attr('rel'),
               success: function(data) {
				 $("#rating_wrapper_"+id).html(data);

                   if(data == 'added') {                                                                        
                        //$('#vote_label2_' + id).html("{'Saved'|get_lang}");
                   }
                   if(data == 'updated') {
                        //$('#vote_label2_' + id).html("{'Saved'|get_lang}");
                   }
               }
           })
       });
});
</script>

{if !(empty($hot_courses)) }	
	 <h3>{"HottestCourses"|get_lang}</h3>
	{foreach $hot_courses as $hot_course}										
		<div class="categories-block-course">
			<div class="categories-content-course">
				
				<div class="categories-course-picture">
					{html_image file=$hot_course.extra_info.course_image}
				</div>
				
				<div class="categories-course-description">
					<div class="course-block-title">{$hot_course.extra_info.name|truncate:60}</div>							
					{$hot_course.extra_info.rating_html}					
				</div>			
			</div>
				
			<div class="categories-course-links">
				<div class="course-link-desc right">
				{if ($hot_course.extra_info.visibility == 3)} 
					<a class="ajax a_button white small" title="" href="{$_p.web_ajax}course_home.ajax.php?a=show_course_information&code={$hot_course.course_code}">
						{"Description"|get_lang}
					</a>
				{/if}								
				</div>

				{* World *}
				{if ($hot_course.extra_info.visibility == 3)}
					<div class="course-link-desc right">
						<a class="a_button gray small" title="" href="{$_p.web_course}{$hot_course.extra_info.path}/index.php">
							{"GoToCourse"|get_lang}
						</a>
					</div>
				{/if}				
			</div>
		</div>
	{/foreach}
{/if}