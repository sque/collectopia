#!/usr/bin/env php
<?php

if (!file_exists('./lib/local/Layout/js.php')) {
        echo "This is not a collectopia folder.\n";
        return;
}

$argv = $_SERVER['argv'];
if (count($argv) != 2) {
        echo "{$argv[0]} <output folder>\n";
        return;
}
$output_folder = $argv[1];

if (file_exists($output_folder)) {
        echo "Output folder \"{$output_folder}\"already exists!\n";
        return;
}

system("cp -R . $output_folder");

// Clean up installation
chdir($output_folder);
system("rm index_dev.php");

// Compress javascript files
$files = include('./lib/local/Layout/js.php');
$collectopia_file = 'static/js/collectopia.pack.min.js';
@unlink($collectopia_file);
foreach($files as $js) {
		$js = substr($js, 1);
		
		if ((substr($js, -6) == 'min.js') || (substr($js, -7) == 'pack.js')){
			echo "File {$js} already compressed ... appending\n";
			file_put_contents($collectopia_file, "\n/** {$js}  **/\n", FILE_APPEND);
			file_put_contents($collectopia_file, file_get_contents($js), FILE_APPEND);
			continue;	
		}		
        
        echo "Compressing {$js}...";
        system("java -jar utils/yuicompressor-2.4.6.jar $js >> $collectopia_file");
        echo "OK\n";
}
file_put_contents('lib/local/Layout/js.php', "<?php return array('/{$collectopia_file}');");

// Compress css files
$files = include('./lib/local/Layout/css.php');
$collectopia_file = 'static/css/collectopia.pack.css';
@unlink($collectopia_file);
foreach($files as $css) {
		$css = substr($css, 1);
		
        echo "Compressing {$css}...";
        system("java -jar utils/yuicompressor-2.4.6.jar $css >> $collectopia_file");
        echo "OK\n";
}
file_put_contents('lib/local/Layout/css.php', "<?php return array('/{$collectopia_file}');");

// Setup permissions
system("mkdir -p private/tmp private/search data/markers data/photos");
system('chmod 0777 private/tmp private/search data/markers data/photos config.inc.php');
