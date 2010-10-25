<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<?php
require 'libwiris.php';

error_reporting(E_ALL);

function wrs_assert($condition) {
	if ($condition) {
		echo '<span class="ok">OK</span>';
	}
	else {
		echo '<span class="error">ERROR</span>';
	}
}
?>
<html>
	<head>
		<title>Plugin WIRIS test page</title>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		
		<style type="text/css">
			/*<!--*/
			
			html {
				font-family: sans-serif;
			}
			
			h2 {
				margin-left: 1em;
			}
			
			h3 {
				margin-left: 2em;
			}
			
			p {
				margin-left: 3em;
			}
			
			p.concrete {
				margin-left: 4em;
			}
			
			.ok {
				font-weight: bold;
				color: #0c0;
			}
			
			.error {
				font-weight: bold;
				color: #f00;
			}
			
			/*-->*/
		</style>
	</head>
	
	<body>
		<h1>Plugin WIRIS test page</h1>
		
		<h2>Loading configuration</h2>
		
		<p>
			<?php
			echo 'Loading ', WRS_CONFIG_FILE, '... ';
			$config = wrs_loadConfig(WRS_CONFIG_FILE);
			wrs_assert(!empty($config));
			?>
		</p>
		
		<h2>Connecting to WIRIS image server</h2>
		
		<p>
			<?php
			echo 'Connecting to ', $config['wirisimageservicehost'], ' on port ', $config['wirisimageserviceport'], '... ';
			wrs_assert(fsockopen($config['wirisimageservicehost'], $config['wirisimageserviceport']));
			?>
		</p>
		
		<h2>Writing a formula file</h2>
		
		<p>
			<?php
			$file = WRS_FORMULA_DIRECTORY . '/test.xml';
			echo 'Writing file ', $file, '... ';
			wrs_assert(fopen($file, 'w') !== false);
			?>
		</p>
		
		<h2>Reading a formula file</h2>
		
		<p>
			<?php
			echo 'Reading file ', $file, '... ';
			wrs_assert(fopen($file, 'r') !== false);
			?>
		</p>
		
		<h2>Writing an image file</h2>
		
		<p>
			<?php
			$file = WRS_CACHE_DIRECTORY . '/test.png';
			echo 'Writing file ', $file, '... ';
			wrs_assert(fopen($file, 'w') !== false);
			?>
		</p>
		
		<h2>Reading an image file</h2>
		
		<p>
			<?php
			echo 'Reading file ', $file, '... ';
			wrs_assert(fopen($file, 'r') !== false);
			?>
		</p>
		
		<h2>PHP tests</h2>
		
		<h3>Checking the existence of PHP functions that Plugin WIRIS uses</h3>
		
		<p class="concrete">
			<?php
			echo 'Checking for fclose...';
			wrs_assert(function_exists('fclose'));
			
			echo '<br/>Checking for fgets...';
			wrs_assert(function_exists('fgets'));
			
			echo '<br/>Checking for file_put_contents...';
			wrs_assert(function_exists('file_put_contents'));
			
			echo '<br/>Checking for fopen...';
			wrs_assert(function_exists('fopen'));
			
			echo '<br/>Checking for http_build_query...';
			wrs_assert(function_exists('http_build_query'));
			
			echo '<br/>Checking for is_file...';
			wrs_assert(function_exists('is_file'));
			
			echo '<br/>Checking for mb_strlen...';
			wrs_assert(function_exists('mb_strlen'));
			
			echo '<br/>Checking for readfile...';
			wrs_assert(function_exists('readfile'));
			?>
		</p>
	</body>
</html>
