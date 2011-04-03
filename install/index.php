<?php

require_once dirname(__FILE__) . '/../lib/vendor/phplibs/ClassLoader.class.php';
require_once dirname(__FILE__) . '/../lib/tools.lib.php';

// Autoloader for local and phplibs classes
$phplibs_loader = new ClassLoader(
    array(
    dirname(__FILE__) . '/../lib/vendor/phplibs',
    dirname(__FILE__) . '/../lib/local'
));
$phplibs_loader->set_file_extension('.class.php');
$phplibs_loader->register();

// Load static library for HTML
require_once dirname(__FILE__) . '/../lib/vendor/phplibs/Output/html.lib.php';

// File names
$fn_config = dirname(__FILE__) . '/../config.inc.php';
$fn_htaccess = dirname(__FILE__) . '/../.htaccess';


$dl = new Layout('debug');
$dl->activate();
$dl->get_document()->add_ref_css(surl('/../static/css/debug.css'));
$dl->get_document()->title = 'Installation';

etag('h2', 'PHPLibs Skeleton');
etag('h3', 'Installation process');

// Make checks for writable files
if (! is_writable($fn_config))
{
    etag('div class="error" nl_escape_on', 'Cannot continue installing ' . Registry::get('site.title') .'
        The configuration file "config.inc.php" must be writable, you can change
        permissions and retry installation.');
    exit;
}

require $fn_config;
$f = new UI_InstallationForm($fn_config, dirname(__FILE__) . '/build.sql');
etag('div', $f->render());
