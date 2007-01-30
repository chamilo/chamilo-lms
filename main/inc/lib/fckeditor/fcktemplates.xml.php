<?php header('Content-Type: text/xml; charset=utf-8');

require_once('../../global.inc.php');
echo '<?xml version="1.0" encoding="utf-8" ?>';
$IMConfig['base_url'] = $_configuration['root_web'].'main/img/gallery/';

$template_css = '<style type="text/css">'.file_get_contents($_configuration['root_sys'].'main/css/'.api_get_setting('stylesheets').'/course.css').'</style>';

?>
<Templates imagesBasePath="fck_template/images/">
	<Template title="Content" image="2.png">
		<Description>Introductory title</Description>
		<Html>
			<![CDATA[
					<?php echo $template_css; ?>
				    <div class="dokeos_course">
						<div id="box_title1">
							<h1>Press here to add a title</h1>
							<h2>Write here a subtitle</h2>
						</div>
						<div id="content1">
						<p><img src="<?php echo $_configuration['root_web']; ?>main/img/gallery/pointer-left.png" alt="Mr dokeos" class="icon" /><p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Curabitur commodo urna in justo. Nulla facilisi. Vestibulum dapibus mattis sapien. Nunc suscipit. Nulla odio urna, faucibus et, porttitor sed, tincidunt in, mauris. Phasellus semper hendrerit magna. Maecenas nec ligula. Quisque tellus tortor, semper in, blandit quis, aliquet ut, tellus. Quisque vulputate. Sed ligula ipsum, interdum vel, congue tincidunt, facilisis eget, arcu. Donec nec ligula et turpis tristique pulvinar. Integer in arcu vel ligula accumsan consectetuer. Ut a erat.</p>
						Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nulla facilisi. Praesent consequat, lectus ac sollicitudin condimentum, nunc felis pharetra ligula, at tincidunt mauris neque vel nulla. Vivamus nec magna vitae leo egestas hendrerit. Fusce sagittis scelerisque sapien. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Praesent convallis. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. In tristique laoreet felis. Ut vitae est. Integer posuere lacus sed libero. Vestibulum cursus. Duis odio arcu, lobortis sed, fringilla non, egestas vel, magna. Aenean suscipit hendrerit nulla. Suspendisse potenti. Proin tincidunt vehicula eros. Quisque eleifend nisi non enim. Aenean elementum.</p>
					    <p>Pellentesque faucibus, magna quis lobortis dapibus, lorem diam pharetra odio, vitae consectetuer nulla massa ac elit. Mauris urna massa, facilisis quis, tristique at, euismod sit amet, tortor. In orci dolor, faucibus sit amet, malesuada non, mollis vel, lacus. Quisque sit amet elit vel eros blandit consequat. Quisque ac risus. Donec accumsan. Suspendisse mauris mi, laoreet ut, pretium vel, dignissim nec, nulla. Sed malesuada facilisis lacus. Phasellus eleifend, nibh vitae pellentesque auctor, libero nunc dictum erat, non imperdiet est dolor quis orci. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi consectetuer euismod mi. Proin sit amet est vitae turpis fermentum sollicitudin. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos.</p>
						</div>
						<div id="footer1">
							<div id="manager1">Course manager: So and So
							</div>
							<div id="email1">E-mail:<a href="mailto:soandso@soandso.com">soandso@soandso.com</a>
							</div>
						</div>
					</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="2.png">
		<Description>Introductory title - Corporative</Description>
		<Html>
			<![CDATA[
					<?php echo $template_css; ?>
					<div class="dokeos_course" id="corp">
						<div id="box_title1">
							<h1>Press here to add a title</h1>
							<h2>Write here a subtitle</h2>
							<div id="bottom"></div>
						</div>
						<div id="content1">
						<p><img src="<?php echo $_configuration['root_web']; ?>main/img/gallery/corp.jpg" alt="Corporative icon"  class="icon" />Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Curabitur commodo urna in justo. Nulla facilisi. Vestibulum dapibus mattis sapien. Nunc suscipit. Nulla odio urna, faucibus et, porttitor sed, tincidunt in, mauris. Phasellus semper hendrerit magna. Maecenas nec ligula. Quisque tellus tortor, semper in, blandit quis, aliquet ut, tellus. Quisque vulputate. Sed ligula ipsum, interdum vel, congue tincidunt, facilisis eget, arcu. Donec nec ligula et turpis tristique pulvinar. Integer in arcu vel ligula accumsan consectetuer. Ut a erat.</p>
						<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nulla facilisi. Praesent consequat, lectus ac sollicitudin condimentum, nunc felis pharetra ligula, at tincidunt mauris neque vel nulla. Vivamus nec magna vitae leo egestas hendrerit. Fusce sagittis scelerisque sapien. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Praesent convallis. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. In tristique laoreet felis. Ut vitae est. Integer posuere lacus sed libero. Vestibulum cursus. Duis odio arcu, lobortis sed, fringilla non, egestas vel, magna. Aenean suscipit hendrerit nulla. Suspendisse potenti. Proin tincidunt vehicula eros. Quisque eleifend nisi non enim. Aenean elementum.</p>
						<p>Pellentesque faucibus, magna quis lobortis dapibus, lorem diam pharetra odio, vitae consectetuer nulla massa ac elit. Mauris urna massa, facilisis quis, tristique at, euismod sit amet, tortor. In orci dolor, faucibus sit amet, malesuada non, mollis vel, lacus. Quisque sit amet elit vel eros blandit consequat. Quisque ac risus. Donec accumsan. Suspendisse mauris mi, laoreet ut, pretium vel, dignissim nec, nulla. Sed malesuada facilisis lacus. Phasellus eleifend, nibh vitae pellentesque auctor, libero nunc dictum erat, non imperdiet est dolor quis orci. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi consectetuer euismod mi. Proin sit amet est vitae turpis fermentum sollicitudin. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos.</p>
						</div>
						<div id="footer1">
							<div class="manager1">Course manager: So and So
							</div>
							<div id="email1">E-mail:<a href="mailto:soandso@soandso.com">soandso@soandso.com</a>
							</div>
						</div>
					</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="2.png">
		<Description>Introductory title - Academic</Description>
		<Html>
			<![CDATA[
					<?php echo $template_css; ?>
					<div class="dokeos_course" id="corp">
						<div id="box_title1">
							<h1>Press here to add a title</h1>
							<h2>Write here a subtitle</h2>
						<div id="bottom"></div>
						</div>
						<div id="content1">
						<p><img src="<?php echo $_configuration['root_web']; ?>main/img/gallery/academic.jpg" alt="Academic icon"  class="icon" />Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Curabitur commodo urna in justo. Nulla facilisi. Vestibulum dapibus mattis sapien. Nunc suscipit. Nulla odio urna, faucibus et, porttitor sed, tincidunt in, mauris. Phasellus semper hendrerit magna. Maecenas nec ligula. Quisque tellus tortor, semper in, blandit quis, aliquet ut, tellus. Quisque vulputate. Sed ligula ipsum, interdum vel, congue tincidunt, facilisis eget, arcu. Donec nec ligula et turpis tristique pulvinar. Integer in arcu vel ligula accumsan consectetuer. Ut a erat.</p>
						<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nulla facilisi. Praesent consequat, lectus ac sollicitudin condimentum, nunc felis pharetra ligula, at tincidunt mauris neque vel nulla. Vivamus nec magna vitae leo egestas hendrerit. Fusce sagittis scelerisque sapien. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Praesent convallis. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. In tristique laoreet felis. Ut vitae est. Integer posuere lacus sed libero. Vestibulum cursus. Duis odio arcu, lobortis sed, fringilla non, egestas vel, magna. Aenean suscipit hendrerit nulla. Suspendisse potenti. Proin tincidunt vehicula eros. Quisque eleifend nisi non enim. Aenean elementum.</p>
						<p>Pellentesque faucibus, magna quis lobortis dapibus, lorem diam pharetra odio, vitae consectetuer nulla massa ac elit. Mauris urna massa, facilisis quis, tristique at, euismod sit amet, tortor. In orci dolor, faucibus sit amet, malesuada non, mollis vel, lacus. Quisque sit amet elit vel eros blandit consequat. Quisque ac risus. Donec accumsan. Suspendisse mauris mi, laoreet ut, pretium vel, dignissim nec, nulla. Sed malesuada facilisis lacus. Phasellus eleifend, nibh vitae pellentesque auctor, libero nunc dictum erat, non imperdiet est dolor quis orci. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi consectetuer euismod mi. Proin sit amet est vitae turpis fermentum sollicitudin. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos.</p>
						</div>
						<div id="footer1">
							<div class="manager1">Course manager: So and So
							</div>
							<div id="email1">E-mail:<a href="mailto:soandso@soandso.com">soandso@soandso.com</a>
							</div>
						</div>
					</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="2.png">
		<Description>Introductory title - Baby</Description>
		<Html>
			<![CDATA[
					<?php echo $template_css; ?>
					<div class="dokeos_course" id="baby">
						<div id="box_title1">
							<h1>Press here to add a title</h1>
							<h2>Write here a subtitle</h2>
							<div id="bottom"></div>
						</div>
						<div id="content1">
						<p><img src="<?php echo $_configuration['root_web']; ?>main/img/gallery/baby.jpg" alt="Baby icon"  class="icon" />Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Curabitur commodo urna in justo. Nulla facilisi. Vestibulum dapibus mattis sapien. Nunc suscipit. Nulla odio urna, faucibus et, porttitor sed, tincidunt in, mauris. Phasellus semper hendrerit magna. Maecenas nec ligula. Quisque tellus tortor, semper in, blandit quis, aliquet ut, tellus. Quisque vulputate. Sed ligula ipsum, interdum vel, congue tincidunt, facilisis eget, arcu. Donec nec ligula et turpis tristique pulvinar. Integer in arcu vel ligula accumsan consectetuer. Ut a erat.</p>
						<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nulla facilisi. Praesent consequat, lectus ac sollicitudin condimentum, nunc felis pharetra ligula, at tincidunt mauris neque vel nulla. Vivamus nec magna vitae leo egestas hendrerit. Fusce sagittis scelerisque sapien. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Praesent convallis. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. In tristique laoreet felis. Ut vitae est. Integer posuere lacus sed libero. Vestibulum cursus. Duis odio arcu, lobortis sed, fringilla non, egestas vel, magna. Aenean suscipit hendrerit nulla. Suspendisse potenti. Proin tincidunt vehicula eros. Quisque eleifend nisi non enim. Aenean elementum.</p>
						<p>Pellentesque faucibus, magna quis lobortis dapibus, lorem diam pharetra odio, vitae consectetuer nulla massa ac elit. Mauris urna massa, facilisis quis, tristique at, euismod sit amet, tortor. In orci dolor, faucibus sit amet, malesuada non, mollis vel, lacus. Quisque sit amet elit vel eros blandit consequat. Quisque ac risus. Donec accumsan. Suspendisse mauris mi, laoreet ut, pretium vel, dignissim nec, nulla. Sed malesuada facilisis lacus. Phasellus eleifend, nibh vitae pellentesque auctor, libero nunc dictum erat, non imperdiet est dolor quis orci. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi consectetuer euismod mi. Proin sit amet est vitae turpis fermentum sollicitudin. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos.</p>
						</div>
						<div id="footer1">
							<div class="manager1">Course manager: So and So
							</div>
							<div id="email1">E-mail:<a href="mailto:soandso@soandso.com">soandso@soandso.com</a>
							</div>
						</div>
					</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="2.png">
		<Description>Explanation</Description>
		<Html>
			<![CDATA[
					<?php echo $template_css; ?>
					<div class="dokeos_course" class="template2">
						<div id="nav2">
							<img src="<?php echo $_configuration['root_web']; ?>main/img/gallery/teacher.png" alt="Mr dokeos"  />
							<div id="list2">
							<p><i>Check out:</i></p>
								<ul>
									<li>web 1</li>
									<li>web 2</li>
									<li>Resource 1</li>
								</ul>
							</div>
						</div>
						<div id="content2">
							<div id="explanation">
							<h1>Explanation</h1>
							</div>
							<div id="main2">
							<h2>Enter text here for your explanation</h2>
						    <p>Pellentesque faucibus, magna quis lobortis dapibus, lorem diam pharetra odio, vitae consectetuer nulla massa ac elit. Mauris urna massa, facilisis quis, tristique at, euismod sit amet, tortor. In orci dolor, faucibus sit amet, malesuada non, mollis vel, lacus. Quisque sit amet elit vel eros blandit consequat. Quisque ac risus. Donec accumsan. Suspendisse mauris mi, laoreet ut, pretium vel, dignissim nec, nulla. Sed malesuada facilisis lacus. Phasellus eleifend, nibh vitae pellentesque auctor, libero nunc dictum erat, non imperdiet est dolor quis orci. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi consectetuer euismod mi. Proin sit amet est vitae turpis fermentum sollicitudin. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos.</p>
							</div>
						</div>
				</div>
			]]>
		</Html>
	</Template>
		<Template title="Content" image="3.jpg">
		<Description>Explanation - Corporative version</Description>
		<Html>
			<![CDATA[
					<?php echo $template_css; ?>
					<div class="dokeos_course" class="template2" id="corp" >
						<div id="nav2">
							<img src="<?php echo $_configuration['root_web']; ?>main/img/gallery/corp.jpg" alt="corporative icon"  />
							<div id="list2">
							<p><i>Check out:</i></p>
								<ul>
									<li>web 1</li>
									<li>web 2</li>
									<li>Resource 1</li>
								</ul>
							</div>
						</div>
						<div id="content2">
							<div id="explanation">
							<h1>Explanation</h1>
							<div id="bottom"></div>
							</div>
							<div id="main2">
							<h2>Enter text here for your explanation</h2>
						    <p>Pellentesque faucibus, magna quis lobortis dapibus, lorem diam pharetra odio, vitae consectetuer nulla massa ac elit. Mauris urna massa, facilisis quis, tristique at, euismod sit amet, tortor. In orci dolor, faucibus sit amet, malesuada non, mollis vel, lacus. Quisque sit amet elit vel eros blandit consequat. Quisque ac risus. Donec accumsan. Suspendisse mauris mi, laoreet ut, pretium vel, dignissim nec, nulla. Sed malesuada facilisis lacus. Phasellus eleifend, nibh vitae pellentesque auctor, libero nunc dictum erat, non imperdiet est dolor quis orci. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi consectetuer euismod mi. Proin sit amet est vitae turpis fermentum sollicitudin. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos.</p>
							</div>
						</div>
				</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="3.jpg">
		<Description>Explanation - Academic version</Description>
		<Html>
			<![CDATA[
					<?php echo $template_css; ?>
					<div class="dokeos_course" class="template2" id="academic" >
					<div id="nav2">
						<img src="<?php echo $_configuration['root_web']; ?>main/img/gallery/academic.jpg" alt="Academic icon" class="logo" />
						<div id="list2">
						<p><i>Check out:</i></p>
							<ul>
								<li>web 1</li>
								<li>web 2</li>
								<li>Resource 1</li>
							</ul>
						</div>
					</div>
					<div id="content2">
						<div id="explanation">
						<h1>Explanation</h1>
						<div id="bottom"></div>
						</div>
						<div id="main2">
						<h2>Enter text here for your explanation</h2>
					    <p>Pellentesque faucibus, magna quis lobortis dapibus, lorem diam pharetra odio, vitae consectetuer nulla massa ac elit. Mauris urna massa, facilisis quis, tristique at, euismod sit amet, tortor. In orci dolor, faucibus sit amet, malesuada non, mollis vel, lacus. Quisque sit amet elit vel eros blandit consequat. Quisque ac risus. Donec accumsan. Suspendisse mauris mi, laoreet ut, pretium vel, dignissim nec, nulla. Sed malesuada facilisis lacus. Phasellus eleifend, nibh vitae pellentesque auctor, libero nunc dictum erat, non imperdiet est dolor quis orci. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi consectetuer euismod mi. Proin sit amet est vitae turpis fermentum sollicitudin. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos.</p>
						</div>
					</div>
			</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="3.jpg">
		<Description>Explanation - Baby version</Description>
		<Html>
			<![CDATA[
					<?php echo $template_css; ?>
					<div class="dokeos_course" class="template2" id="baby" >
					<div id="nav2">
						<img src="<?php echo $_configuration['root_web']; ?>main/img/gallery/baby.jpg" alt="baby icon" class="logo" />
						<div id="list2">
						<p><i>Check out:</i></p>
							<ul>
								<li>web 1</li>
								<li>web 2</li>
								<li>Resource 1</li>
							</ul>
						</div>
					</div>
					<div id="content2">
						<div id="explanation">
						<h1>Explanation</h1>
						<div id="bottom"></div>
						</div>
						<div id="main2">
						<h2>Enter text here for your explanation</h2>
					    <p>Pellentesque faucibus, magna quis lobortis dapibus, lorem diam pharetra odio, vitae consectetuer nulla massa ac elit. Mauris urna massa, facilisis quis, tristique at, euismod sit amet, tortor. In orci dolor, faucibus sit amet, malesuada non, mollis vel, lacus. Quisque sit amet elit vel eros blandit consequat. Quisque ac risus. Donec accumsan. Suspendisse mauris mi, laoreet ut, pretium vel, dignissim nec, nulla. Sed malesuada facilisis lacus. Phasellus eleifend, nibh vitae pellentesque auctor, libero nunc dictum erat, non imperdiet est dolor quis orci. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi consectetuer euismod mi. Proin sit amet est vitae turpis fermentum sollicitudin. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos.</p>
						</div>
					</div>
			</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="3.png">
		<Description>Course Objectives</Description>
		<Html>
			<![CDATA[
					<?php echo $template_css; ?>
					<div class="dokeos_course" class="template3">
						<div id="box_title3">
							<h1>Course objetives</h1>
						</div>
						<div id="content3">
						<ul id="primary">
							<li>Objetive number 1</li>
							<ul class="secondary"><li>Short comment of the Objective 1</li></ul>
							<li>Objetive number 2</li>
							<ul class="secondary"><li>Short comment of the Objective 2</li></ul>
							<li>Objetive number 3</li>
							<ul class="secondary"><li>Short comment of the Objective 3</li></ul>
						</ul>
						<div id="footer3">
							<img src="<?php echo $_configuration['root_web']; ?>main/img/gallery/pointer-right.png" alt="Mr dokeos" class="logo" />
						</div> 
						</div>	
				</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="3.png">
		<Description>Course Objectives - Corporative version</Description>
		<Html>
			<![CDATA[
					<?php echo $template_css; ?>
				<div class="dokeos_course" class="template3" id="corp">
					<div id="box_title3">
						<h1>Course objetives</h1>
						<div id="bottom"></div>
					</div>
					<div id="content3">
					<ul id="primary">
						<li>Objetive number 1</li>
						<ul class="secondary"><li>Short comment of the Objective 1</li></ul>
						<li>Objetive number 2</li>
						<ul class="secondary"><li>Short comment of the Objective 2</li></ul>
						<li>Objetive number 3</li>
						<ul class="secondary"><li>Short comment of the Objective 3</li></ul>
					</ul>
					<div id="footer3">
						<img src="<?php echo $_configuration['root_web']; ?>main/img/gallery/corp_right.jpg" alt="Corporative logo" class="logo" />
					</div> 
					</div>	
			</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="3.png">
		<Description>Course Objectives - Academic version</Description>
		<Html>
			<![CDATA[
					<?php echo $template_css; ?>
				<div class="dokeos_course" class="template3" id="academic">
					<div id="box_title3">
						<h1>Course objetives</h1>
						<div id="bottom"></div>
					</div>
					<div id="content3">
					<ul id="primary">
						<li>Objetive number 1</li>
						<ul class="secondary"><li>Short comment of the Objective 1</li></ul>
						<li>Objetive number 2</li>
						<ul class="secondary"><li>Short comment of the Objective 2</li></ul>
						<li>Objetive number 3</li>
						<ul class="secondary"><li>Short comment of the Objective 3</li></ul>
					</ul>
					<div id="footer3">
						<img src="<?php echo $_configuration['root_web']; ?>main/img/gallery/academic.jpg" alt="Academic logo" class="logo" />
					</div> 
					</div>	
			</div>
			]]>
		</Html>
	</Template>
	<Template title="Content" image="3.png">
		<Description>Course Objectives - Baby</Description>
		<Html>
			<![CDATA[
					<?php echo $template_css; ?>
					<div class="dokeos_course" class="template3" id="baby">
						<div id="box_title3">
							<h1>Course objetives</h1>
							<div id="bottom"></div>
						</div>
						<div id="content3">
						<ul id="primary">
							<li>Objetive number 1</li>
							<ul class="secondary"><li>Short comment of the Objective 1</li></ul>
							<li>Objetive number 2</li>
							<ul class="secondary"><li>Short comment of the Objective 2</li></ul>
							<li>Objetive number 3</li>
							<ul class="secondary"><li>Short comment of the Objective 3</li></ul>
						</ul>
						<div id="footer3">
							<img src="<?php echo $_configuration['root_web']; ?>main/img/gallery/baby.jpg" alt="Baby logo" class="logo" />
						</div> 
						</div>	
				</div>
			]]>
		</Html>
	</Template>
</Templates>


