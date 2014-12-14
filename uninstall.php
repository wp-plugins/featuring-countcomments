<?php

/*
http://codex.wordpress.org/Function_Reference/register_uninstall_hook#uninstall.php

Please note: Due to the uninstall procedure of WordPress you have to delete left-over database entries in multisite environments manually for each blog.
*/

/*
security check
*/

if (!defined( 'WP_UNINSTALL_PLUGIN'))
	wp_die(__('Cheatin&#8217; uh?'), '', array('response' => 403));

if (!current_user_can('manage_options'))
	wp_die(__('You do not have sufficient permissions to manage options for this site.'), '', array('response' => 403));

/*
delete option-array
*/

delete_option('featuring_countcomments');

/*
delete widget-options
*/

delete_option('widget_featuring_countcomments');
