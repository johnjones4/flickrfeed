<?php
/*
Plugin Name: Flickr Feed
Plugin URI: http://johnjonesfour.com/flickrfeed
Description: Feed a flickr handle into a content type
Author: John Jones
Version: 1.0
Author URI: http://johnjonesfour.com
*/

/*
 * Constants
 */
define('FLICKR_SETTINGS_GROUP','flickr_settings_group');
define('FLICKR_SETTINGS_USERID','flickr_userid');
define('FLICKR_ID_POST_TYPE','flickr');
define('FLICKR_ID_META_KEY','flickr_id');
define('FLICKR_ID_META_URL','flickr_url');
define('FLICKR_ID_URL_PREFIX','http://flickr.com/photo.gne?id=');
define('FLICKR_API_KEY','5dbf3c8d69a4ead9f6c8d01b4c523eb5');

/*
 * Actions
 */
add_action('init','flickrfeed_init');
add_action('flickrfeed_cron_hook', 'flickrfeed_cron_function');
add_action('admin_menu', 'flickrfeed_admin_menu'); 
add_action('admin_init', 'flickrfeed_admin_init' );
add_filter('pre_get_posts', 'flickrfeed_get_posts' );
add_filter('the_content', 'flickrfeed_the_content');
add_filter('the_excerpt', 'flickrfeed_the_content'); 
add_filter('post_type_link', 'flickrfeed_post_link');

if ( !wp_next_scheduled('flickrfeed_cron_hook') ) {
	wp_schedule_event( time(), 'hourly', 'flickrfeed_cron_hook' ); // hourly, daily and twicedaily
}


/*
 * Callbacks
 */
function flickrfeed_init() {
	register_post_type(FLICKR_ID_POST_TYPE,
		array(
			'labels' => array(
				'name' => __( 'Flickr Photos' ),
				'singular_name' => __( 'Flickr Photo' )
			),
			'public' => true,
			'has_archive' => true,
			'supports' => array('title','author','thumbnail','excerpt','comments')
		)
	);
	if (!flickrfeed_photos_any()) {
		flickrfeed_cron_function();
	}
}
 
function flickrfeed_cron_function() {
	global $user_ID;
	$apikey = FLICKR_API_KEY;
	$userid = get_option(FLICKR_SETTINGS_USERID);
	$perpage = 500;
	$url = sprintf('http://api.flickr.com/services/rest/?format=json&method=flickr.photos.search&api_key=%s&user_id=%s&per_page=%i&extras=date_taken,geo,url_l&sort=date-taken-desc&jsoncallback=?',$apikey,$userid,$perpage);
	//echo $url;
	$json = file_get_contents($url);
	$json = str_replace( 'jsonFlickrApi(', '', $json );
	$json = substr( $json, 0, strlen( $json ) - 1 );
	$data = json_decode($json,true);
	$photos = $data['photos']['photo'];
	//print_r($photos);
	foreach($photos as $photo) {
		if (!flickrfeed_photo_exists($photo['id'])) {
			$new_post = array(
				'post_title' => $photo['title'],
				'post_status' => 'publish',
				'post_date' => $photo['datetaken'],
				'post_author' => $user_ID,
				'post_type' => FLICKR_ID_POST_TYPE,
				'post_category' => array(0)
			);
			$post_id = wp_insert_post($new_post);
			update_post_meta($post_id,FLICKR_ID_META_KEY,$photo['id']);
			update_post_meta($post_id,FLICKR_ID_META_URL,$photo['url_l']);
			//media_sideload_image($photo['url_l'],$new_post);
		}
	}
}

function flickrfeed_get_posts($query) {
	if ( $query->is_home || $query->is_category || $query->is_archive || $query->is_tag || $query->is_date || $query->is_year || $query->is_month || $query->is_day || $query->is_time || $query->is_author || $query->is_tax || $query->is_search || $query->is_feed) {
		if (is_array($query->query_vars['post_type'])) {
			$query->query_vars['post_type'][] = 'flickr';
		} else {
			$query->query_vars['post_type'] = array('flickr','post');
		}
	}
}

function flickrfeed_the_content($content) {
	global $post;
	if ($post->post_type == FLICKR_ID_POST_TYPE) {
		$src = get_post_meta($post->ID,FLICKR_ID_META_URL,true);
		return sprintf('<a class="flickr" href="%s"><img src="%s" class="flickr" rel="flickr" /></a>',$src,$src);
	} else {
		return $content;
	}
}

function flickrfeed_post_link($permalink) {
	global $post;
	if ($post->post_type == FLICKR_ID_POST_TYPE) {
		return flickrfeed_pageurl($post->ID);
	} else {
		return $permalink;
	}
}

function flickrfeed_admin_menu() {
	add_options_page("FlickrFeed", "FlickrFeed", 1, "FlickrFeed", "flickrfeed_admin");
	
	
}

function flickrfeed_admin() {
	include 'flickrfeed_admin_page.php';
}

function flickrfeed_admin_init() {
	register_setting(FLICKR_SETTINGS_GROUP,FLICKR_SETTINGS_USERID);
}


/*
 * Other Functions
 */
function flickrfeed_photos_any() {
	return count(get_posts(array('post_type'=>'flickr'))) > 0;
}

function flickrfeed_photo_exists($id) {
	return count(get_posts(array('post_type'=>FLICKR_ID_POST_TYPE,'meta_key'=>FLICKR_ID_META_KEY,'meta_value'=>$id))) > 0;
}

function flickrfeed_pageurl($id) {
	return FLICKR_ID_URL_PREFIX.get_post_meta($id,FLICKR_ID_META_KEY,true);
}
	