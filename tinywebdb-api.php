<?php
/*
Plugin Name: Wp TinyWebDB API
Plugin URI: http://edu2web.com/tinywebdb-api/
Description: a AppInventor TinyWebDB API plugin, use you WordPress as a TinyWebDB web service.
    Action        URL                      Post Parameters  Response
    Get Value     {ServiceURL}/getvalue    tag              JSON: ["VALUE","{tag}", {value}]
    Store A Value {ServiceURL}/storeavalue tag,value        JSON: ["STORED", "{tag}", {value}]
Author: Hong Chen
Author URI: http://digilib.net/
Version: 0.2.2
*/


define("TINYWEBDB", "tools.php?page=tinywebdb-api/tinywebdb-api.php");
define("TINYWEBDB_VER", "0.2.2");

//***** Hooks *****
register_activation_hook(__FILE__,'wp_tinywebdb_api_install'); //Install
add_action('template_redirect', 'wp_tinywebdb_api_query'); //Redirect
add_action('admin_menu', 'wp_tinywebdb_api_add_pages'); //Admin pages
//***** End Hooks *****


//***** Installer *****
if (is_admin()) {
	include "installer.php";
}


//***** get $request and get_post , then json_encode it *****

add_filter('query_vars', 'add_fetch');
function add_fetch($public_query_vars) {
	$public_query_vars[] = 'tag';
	$public_query_vars[] = 'value';
	$public_query_vars[] = 'apikey';
	return $public_query_vars;
}

function wp_tinywebdb_api_query() {


	require_once dirname(__FILE__) . '/tinywebdb.php';
	$tinywebdb = TinyWebDB;
	header("HTTP/1.1 200 OK");

	switch (TinyWebDB::get_action()) {
		case "getvalue":
			$tagName = get_query_var('tag');
			$tagValue = TinyWebDB::getvalue($tagName);

			header('Cache-Control: no-cache, must-revalidate');
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Content-type: application/json');
			echo json_encode(array("VALUE", $tagName, $tagValue));
			exit; // this stops WordPress entirely
			break;
		case "storeavalue": // this action will enable from v 0.2.x
			// JSON_API , Post Parameters : tag,value
			$tagName = get_query_var('tag');
			$tagValue = get_query_var('value');	             // $_REQUEST['value']; //
			$apiKey = get_query_var('apikey');
			error_log("Wp TinyWebDB API : storeavalue: " . __FILE__ . "/" . __LINE__ . " ($apiKey) $tagName -- $tagValue");
			$setting_apikey = get_option("wp_tinywebdb_api_key");
			if ($apiKey == $setting_apikey){

				$postid = TinyWebDB::storeavalue($tagName, $tagValue);
				$tagName = TinyWebDB::wp_tinywebdb_api_get_tagName($postid);

				header('Cache-Control: no-cache, must-revalidate');
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
				header('Content-type: application/json');
				echo json_encode(array("STORED", $tagName, $tagValue));
			} else {
				echo "check api key.";
			}
		    exit;
			break;
		case "No match!":
			break;
		default:
			echo '{"status":"ok","tinywebdb_api_version":"' . TINYWEBDB_VER . '","controllers":["getvalue","storeavalue"]}' . "\n";
			exit; // this stops WordPress entirely
			break;
	}
}
//***** End get $request and call JSON_API *****



//Just a boring function to insert the menus
function wp_tinywebdb_api_add_pages() {
	add_options_page("TinyWebDB Settings", "TinyWebDB API", "manage_options", __FILE__, "wp_tinywebdb_api_optionsmenu");
}



//***** Menu *****
if (is_admin()) {
	include "menus.php";
}



//***** Text Truncation Helper Function *****
function wp_tinywebdb_api_truncate($text) {
	if ( strlen($text) > 79 ) {
		$text = $text." ";
		$text = substr($text,0,80);
		$text = $text."...";
		return $text;
	} else { return $text; }
}



//***** Get Plugin Location *****
function wp_tinywebdb_api_get_plugin_dir($type) {
	if ( !defined('WP_CONTENT_URL') )
		define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
	if ( !defined('WP_CONTENT_DIR') )
		define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	if ($type=='path') { return WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__)); }
	else { return WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__)); }
}



//***** Add Item to Favorites Menu *****
function wp_tinywebdb_api_add_menu_favorite($actions) {
	$actions[TINYWEBDB] = array('TinyWebDB', 'manage_options');
	return $actions;
}
add_filter('favorite_actions', 'wp_tinywebdb_api_add_menu_favorite'); //Favorites Menu



?>
