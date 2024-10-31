<?php
if ( !defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
}
/* delete custom option*/
delete_option('podcast-setting');
/* delete custom post type posts*/
$myplugin_args = array('post_type' => 'podcast', 'post_status'=>'any' ,'posts_per_page' => -1);
$myplugin_posts = get_posts($myplugin_args);
foreach ($myplugin_posts as $post) {
	wp_delete_post($post->ID, true);
}