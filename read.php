<?php
include 'inc/functions.php';
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//var_dump(cache::get("thread_hello_1"));

$query = $_SERVER['QUERY_STRING'];

$pages = array(
	'/(\%b)/?'										=> 'view_board',
	'/(\%b)/(\d+)\.json'									=> 'view_api_index',
	'/(\%b)/catalog\.json'									=> 'view_api_catalog',
	'/(\%b)/threads\.json'									=> 'view_api_threads',
	'/(\%b)/config\.json'									=> 'view_api_config',
	'/(\%b)/main\.js'									=> 'view_js',
	'/main\.js'										=> 'view_js',
	'/(\%b)/catalog(\.html)?'								=> 'view_catalog',
	'/(\%b)/' . preg_quote($config['file_index'], '!')					=> 'view_board',
	'/(\%b)/' . str_replace('%d', '(\d+)', preg_quote($config['file_page'], '!'))		=> 'view_board',
	'/(\%b)/' . preg_quote($config['dir']['res'], '!') .
			str_replace('%d', '(\d+)', '%d\+50(\.html)?')	=> 'view_thread50',
	'/(\%b)/' . preg_quote($config['dir']['res'], '!') .
			str_replace('%d', '(\d+)', '%d(\.html)?')	=> 'view_thread',
);

$new_pages = array();
foreach ($pages as $key => $callback) {
	if (is_string($callback) && preg_match('/^secure /', $callback))
		$key .= '(/(?P<token>[a-f0-9]{8}))?';
	$key = str_replace('\%b', '?P<board>' . sprintf(substr($config['board_path'], 0, -1), $config['board_regex']), $key);
	$new_pages[@$key[0] == '!' ? $key : '!^' . $key . '(?:&[^&=]+=[^&]*)*$!u'] = $callback;
}
$pages = $new_pages;

function view_thread50($boardName, $thread) {
	global $config, $mod;
	
	if (!openBoard($boardName)) {
		include '404.php';
		return;
	}
	
	$page = buildThread50($thread, true, false);
	echo $page;
}

function view_thread($boardName, $thread) {
	global $config, $mod;
	
	if (!openBoard($boardName)) {
		include '404.php';
		return;
	}
	
	$page = buildThread($thread, true, false);
	echo $page;
}

function view_api_index($boardName, $page) {
	global $config, $board;

	$api = new Api();

	if (!openBoard($boardName)) {
		include '404.php';
		return;
	}

	$content = index($page+1);

	if (!$content)
		return;

	if ($config['api']['enabled']) {
		$threads = $content['threads'];
		$json = json_encode($api->translatePage($threads));
		header('Content-Type: text/json');

		echo $json;
	}
}

function APICatalog($boardName, $gen_threads = false) {
	global $config;
	if (!openBoard($boardName)) {
		include '404.php';
		return;
	}

	header('Content-Type: application/json');

	$catalog = array();
	$api = new Api();

	for ($page = 1; $page <= $config['max_pages']; $page++) {
		$content = index($page);
		
		if (!$content)
			break;

		$catalog[$page-1] = $content['threads'];
	}

	echo json_encode($api->translateCatalog($catalog, $gen_threads));
}

function view_api_catalog($boardName) {
	APICatalog($boardName, false);
}

function view_api_threads($boardName) {
	APICatalog($boardName, true);
}

function view_api_config($boardName) {
	global $config;
	if (!openBoard($boardName)) {
		include '404.php';
		return;
	}

	$banners = file_exists("static/banners/$boardName") ? array_slice(scandir('static/banners/'.$boardName), 2) : array();

	header('Content-Type: application/json');
	$output = array(
		'uri' => $boardName,
		'title' => $config['title'],
		'subtitle' => $config['subtitle'],
		'user_flags_enabled' => $config['user_flag'],
		'user_flags' => $config['user_flags'],
		'banners' => $banners,
		'forced_anon' => $config['field_disable_name'],
		'embedding_enabled' => $config['enable_embedding'],
		'require_op_file' => $config['force_image_op'],
		'images_disabled' => $config['disable_images'],
		'poster_ids' => $config['poster_ids'],
		'show_sages' => $config['show_sages'],
		'auto_unicode' => $config['auto_unicode'],
		'indexed' => $config['indexed'],
		'public_bans' => $config['public_bans'],
		'8archive' => $config['8archive'],
		'code_enabled' => $config['code_enabled'],
		'katex_enabled' => $config['katex'],
		'allowed_filetypes' => array_merge($config['allowed_ext'], $config['allowed_ext_files']),
		'dice_enabled' => $config['allow_roll'],
		'no_duplicate_files' => $config['image_reject_repost'],
		'delete_enabled' => $config['allow_delete'],
		'language' => $config['locale'],
		'max_files' => $config['max_images'],
		'default_name' => $config['anonymous'],
		'announcement' => $config['blotter'],
		'captcha_enabled' => $config['captcha']['enabled'],
		'captcha_extra' => $config['captcha']['extra'],
		'max_filesize' => $config['max_filesize'],
		'max_body' => $config['max_body']
	);

	echo json_encode($output);
}

function view_board($boardName, $page_no = 1) {
	global $config, $mod;
	
	if (!openBoard($boardName)) {
		include '404.php';
		return;
	}
	
	if (!$page = index($page_no, $mod)) {
		error($config['error']['404']);
	}
	
	$page['pages'] = getPages(false);
	$page['pages'][$page_no-1]['selected'] = true;
	$page['btn'] = getPageButtons($page['pages'], false);
	$page['mod'] = false;
	$page['config'] = $config;
	
	echo Element('index.html', $page);
}

function view_js($boardName = false) {
	global $config;

	if ($boardName && !openBoard($boardName))
		error($config['error']['noboard']);

	if (!$boardName) {
		$cache_name = 'main_js';
	} else {
		$cache_name = "board_{$boardName}_js";
	}

	if (!($script = cache::get($cache_name))) {
		$script = buildJavascript();
	}

	echo $script;
}

function view_catalog($boardName) {
	global $board, $config;
	$_theme = 'catalog';

	$theme = loadThemeConfig($_theme);

	if (!openBoard($boardName)) {
		include '404.php';
		return;
	}

	require_once $config['dir']['themes'] . '/' . $_theme . '/theme.php';

	$catalog = $theme['build_function']('read_php', themeSettings($_theme), $board['uri']);
	echo $catalog;
}

$found = false;
foreach ($pages as $uri => $handler) {
	if (preg_match($uri, $query, $matches)) {
		$matches = array_slice($matches, 2);
		if (is_callable($handler)) {
			$found = true;
			call_user_func_array($handler, $matches);
		}
	}
}

if (!$found)
	include '404.php';
