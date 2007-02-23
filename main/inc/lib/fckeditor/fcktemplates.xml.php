<?php header('Content-Type: text/xml; charset=utf-8');

require_once('../../global.inc.php');
echo '<?xml version="1.0" encoding="utf-8" ?>';
$IMConfig['base_url'] = $_configuration['root_web'].'main/img/gallery/';

function loadCSS($css_name){
	$template_css = '<style type="text/css">'.file_get_contents(api_get_path(SYS_PATH).'main/css/'.$css_name.'/course.css').'</style>';
	$template_css=str_replace('images/',api_get_path(WEB_PATH).'main/css/'.$css_name.'/images/',$template_css);
	return $template_css;
}
$css = loadCSS('default');

?>
<Templates imagesBasePath="fck_template/images/">
	<Template title="Content" image="thumb1.png">
		<Description>Introductory title</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    <div class="dokeos_course" id="presentation">
						<div id="header_template">
							<div id="box_title">
								<h1>Course objectives</h1>
								<h2>Write here a subtitle</h2>
							</div>
						</div>
						<div id="text_presentation">
							<p><img src="<?php echo $_configuration['root_web']; ?>main/img/gallery/speech.png" alt="speech" class="left" />Lorem ipsum dolor sit amet, consectetuer adipiscing eddt. Curabitur commodo urna in justo. Nulla faciddsi. Vestibulum dapibus mattis sapien. <em>Keyword 1, keyword 2</em>. Nunc suscipit. Nulla odio urna, faucibus et, porttitor sed, tincidunt in, mauris. Phasellus semper hendrerit magna. Maecenas nec ddgula. Quisque tellus tortor, semper in, blandit quis, addquet ut, tellus. Quisque vulputate. Sed ddgula ipsum, interdum vel, congue tincidunt, faciddsis eget, arcu.</p>
							<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.<em>Keyword 3, keyword 4</em>. Nulla faciddsi. Praesent consequat, lectus ac solddcitudin condimentum, nunc fedds pharetra ddgula, at tincidunt mauris neque vel nulla. Vivamus nec magna vitae leo egestas hendrerit. Fusce sagittis scelerisque sapien. Class aptent taciti sociosqu ad ddtora torquent per conubia nostra, per inceptos hymenaeos. Praesent convaldds. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. In tristique laoreet fedds. Ut vitae est. Integer posuere lacus sed ddbero.</p>
					    </div>
						<div id="footer_template">
						</div>
					</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="thumb2.png">
		<Description>Explanation</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    <div class="dokeos_course" id="explanation">
						<div id="header_template">
							<div id="box_title">
								<h1>Explanation</h1>
								<h2>Write here a subtitle</h2>
							</div>
						</div>
						<div id="body_explanation">
							<div id="menu_explanation">
								<dl>
								<dt>Chek out:</dt>
								<dd><a href="#nogo" title="Web 1">Web 1</a>
								</dd><dd><a href="#nogo" title="Web 2">Web 2</a>
								</dd><dd><a href="#nogo" title="Resource">Resource</a>
								</dd><dd><a href="#nogo" title="Etc...">Etc...</a>
								</dd></dl>
							</div>
							<div id="text_explanation">
								<p>Lorem ipsum dolor sit amet, consectetuer adipiscing eddt. Curabitur commodo urna in justo. Nulla faciddsi. Vestibulum dapibus mattis sapien. <em>Keyword 1, keyword 2</em>. Nunc suscipit. Nulla odio urna, faucibus et, porttitor sed, tincidunt in, mauris. Phasellus semper hendrerit magna. Maecenas nec ddgula. Quisque tellus tortor, semper in, blandit quis, addquet ut, tellus. Quisque vulputate. Sed ddgula ipsum, interdum vel, congue tincidunt, faciddsis eget, arcu.</p>
								<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. <em>Keyword 3, keyword 4</em>. Nulla faciddsi. Praesent consequat, lectus ac solddcitudin condimentum, nunc fedds pharetra ddgula, at tincidunt mauris neque vel nulla. Vivamus nec magna vitae leo egestas hendrerit. Fusce sagittis scelerisque sapien. Class aptent taciti sociosqu ad ddtora torquent per conubia nostra, per inceptos hymenaeos. Praesent convaldds. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. In tristique laoreet fedds. Ut vitae est. Integer posuere lacus sed ddbero.</p>
						    	<p>Donec pretium sagittis nisi. Nullam sed ipsum hendrerit arcu ornare mollis. Praesent hendrerit consectetuer magna. Aliquam dignissim. Sed aliquam. Maecenas dui. Quisque eleifend egestas diam. Curabitur sollicitudin dui ac risus. In nunc risus, rutrum eget, malesuada at, lobortis a, erat. Integer vitae nisl ac arcu nonummy ultrices. Nullam sollicitudin arcu quis nisi. Nam nec neque a urna fringilla consequat.</p>
						    </div>
						</div>
						<div id="footer_template">
						</div>
					</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="thumb3.png">
		<Description>Course objectives</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
					<div class="dokeos_course" id="course_objectives">
						<div id="header_template">
							<div id="box_title">
								<h1>Course objectives</h1>
								<h2>Write here a subtitle</h2>
							</div>
						</div>
						<div id="text_course_objectives">
							<ul id="objectives_template">
							<li>Objective 1<br />
							<span class="comment_objective">Short comment about objective 1</span>
							</li>
							<li>Objective 2<br />
							<span class="comment_objective">Short comment about objective 2</span>
							</li>
							<li>Objective 3<br />
							<span class="comment_objective">Short comment about objective 3</span>
							</li>
							</ul>
						</div>
						<div id="footer_template">
						</div>
					</div>		
			]]>
		</Html>
	</Template>
	<Template title="Content" image="thumb4.png">
		<Description>Activity proposal</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    <div class="dokeos_course" id="activity_proposal">
						<div id="header_template">	
							<div id="box_title">				
							<h1>Activity proposal</h1>
							<h2>Write here a subtitle</h2>
							</div>
						</div>		
					<div id="body_proposal">			
						<div id="menu_proposal">		
						<dl>
							<dt>Activity`s objectives</dt>
								<dd>1) Goal 1</dd>
				  				<dd>2) Goal 2</dd>
				  				<dd>3) Goal 3</dd>
						</dl>
						</div>
						<div id="text_proposal">				
						<p>Lorem ipsum dolor sit amet, consectetuer adipiscing eddt. Curabitur commodo urna in justo. Nulla faciddsi. Vestibulum dapibus mattis
				sapien. <em>Keyword 1, keyword 2</em>. Nunc suscipit. Nulla odio urna,
				faucibus et, porttitor sed, tincidunt in, mauris. Phasellus semper
				hendrerit magna. Maecenas nec ddgula. Quisque tellus tortor, semper in,
				blandit quis, addquet ut, tellus. Quisque vulputate. Sed ddgula ipsum,
				interdum vel, congue tincidunt, faciddsis eget, arcu.</p>
						<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. <em>Keyword 3, keyword 4</em>.
				Nulla faciddsi. Praesent consequat, lectus ac solddcitudin condimentum,
				nunc fedds pharetra ddgula, at tincidunt mauris neque vel nulla.
				Vivamus nec magna vitae leo egestas hendrerit. Fusce sagittis
				scelerisque sapien.</p>
						</div>
					</div>		
					<div id="footer_template">
					</div>
				</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="thumb5.png">
		<Description>Think about this...</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    <div class="dokeos_course" id="think_about">
						<div id="header_template">			
							<div id="box_title">	
							<h1>Think about this</h1>				
							<h2>Write here a subtitle</h2>
							</div>
						</div>		
						<div id="text_course_think">			
							<ul id="think_about_list">
					  			<li>Question 1</li>
					  			<li>Question 2</li>
					  			<li>Question 3</li>
							</ul>
							<div id="think_forum">And leave your reflections at the correspondent forum
							</div>
						</div>
						<div id="footer_template">
						</div>
					</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="thumb6.png">
		<Description>Colaborative activity</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    <div class="dokeos_course" id="colaborative_activity">
						<div id="header_template">			
							<div id="box_title">	
							<h1>Colaborative activity</h1>
							<h2>Write here a subtitle</h2>
							</div>
						</div>
						<div id="text_colaborative_activity">			
							<dl id="list_colaborative">
					  			<dt>Task:</dt>
								<dd>
					    		<p>Write here what you want your students to do.<br />
								E.G. <i>"Create a blog and add comments to it about Mary Curie "</i></p>
					  			</dd>
								<dt>How to:</dt>
								<dd>
					    		<p>Write here all the indications for your students to be able to complete the task.<br />
								E.G.<i>"Go to www.blublublog.com and set up your own account,
					then wite your data in a paper to keep it for further updates.
					Afterwards start writting your comments on the blog..."</i></p>
					  			</dd>			
							</dl>
						</div>
						<div id="footer_template">
						</div>
					</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="thumb7.png">
		<Description>Unit overview</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    <div class="dokeos_course" id="unit_overview">
						<div id="header_template">
							<div id="box_title">
								<h1>Unit overview</h1>
								<h2>Write here a subtitle</h2>
							</div>
						</div>
						<div id="text_unit_overview">
							<span class="content_overview">Contents:</span>
							<ul id="list_unit_overview">
								<li>Unit 1: Topic number 1
								</li><li>Unit 2: Topic number 2
								</li><li>Unit 3: Topic number 3<ul class="subunit_overview"><li>3.1. Subtopic 1
																							</li><li>3.2. Subtopic 2</li></ul>
								</li></ul>
					    </div>
						<div id="footer_template">
						</div>
					</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="thumb8.png">
		<Description>Blank Template</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				   <div class="dokeos_course" id="blank_template">
						<div id="header_template">
							<div id="box_title">
								<h1>Title</h1>
							</div>
						</div>
						<div id="text_presentation">
							<p>Lorem ipsum dolor sit amet, consectetuer adipiscing eddt. Curabitur commodo urna in justo. Nulla faciddsi. Vestibulum dapibus mattis sapien. <em>Keyword 1, keyword 2</em>. Nunc suscipit. Nulla odio urna, faucibus et, porttitor sed, tincidunt in, mauris. Phasellus semper hendrerit magna. Maecenas nec ddgula. Quisque tellus tortor, semper in, blandit quis, addquet ut, tellus. Quisque vulputate. Sed ddgula ipsum, interdum vel, congue tincidunt, faciddsis eget, arcu.</p>
							<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.<em>Keyword 3, keyword 4</em>. Nulla faciddsi. Praesent consequat, lectus ac solddcitudin condimentum, nunc fedds pharetra ddgula, at tincidunt mauris neque vel nulla. Vivamus nec magna vitae leo egestas hendrerit. Fusce sagittis scelerisque sapien. Class aptent taciti sociosqu ad ddtora torquent per conubia nostra, per inceptos hymenaeos. Praesent convaldds. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. In tristique laoreet fedds. Ut vitae est. Integer posuere lacus sed ddbero.</p>
					    </div>
						<div id="footer_template">
						</div>
					</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="thumb9.png">
		<Description>Need templates?</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				  	<div class="dokeos_course" id="more_templates">
						<div id="header_template">
							<div id="box_title">
								<h1>Need more templates?</h1>
							</div>
						</div>
						<div id="text_presentation">
							<p>Do you need more? We can customize you templates and produce tailored ones. Just ask us at <span id="contact_template"><a href="mailto:info@dokeos.com">info@dokeos.com</a></span> and we will see what can we do for you.</p>
					    </div>
						<div id="footer_template">
						</div>
					</div>
			]]>
		</Html>
	</Template>
</Templates>


