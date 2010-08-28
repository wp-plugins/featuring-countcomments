<?php

/*
Plugin Name: Featuring CountComments
Plugin URI: http://www.neotrinity.at/projects/
Description: Counts the number of comments for each user who has been logged in at the time of commenting.
Author: Dr. Bernhard Riedl
Version: 1.11
Author URI: http://www.bernhard.riedl.name/
*/

/*
Copyright 2006-2010 Dr. Bernhard Riedl

Inspirations & Proof-Reading 2007-2010
by Veronika Grascher
original idea by Martijn van der Kwast

This program is free software:
you can redistribute it and/or modify
it under the terms of the
GNU General Public License as published by
the Free Software Foundation,
either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope
that it will be useful,
but WITHOUT ANY WARRANTY;
without even the implied warranty of
MERCHANTABILITY or
FITNESS FOR A PARTICULAR PURPOSE.

See the GNU General Public License
for more details.

You should have received a copy of the
GNU General Public License
along with this program.

If not, see http://www.gnu.org/licenses/.
*/

/*
global instance
*/

global $featuring_countcomments;

if (empty($featuring_countcomments) || !is_object($featuring_countcomments) || !$featuring_countcomments instanceof FeaturingCountComments)
	$featuring_countcomments=new FeaturingCountComments();

/*
Class
*/

class FeaturingCountComments {

	/*
	prefix for fields, option, etc.
	*/

	private $prefix='featuring_countcomments';

	/*
	nicename for options-page,
	meta-data, etc.
	*/

	private $nicename='Featuring CountComments';

	/*
	plugin_url with trailing slash
	*/

	private $plugin_url;

	/*
	array with comment-counts,
	array-keys are user-ids
	*/

	private $cache=array();

	/*
	search_attributes
	*/

	private $search_attributes=array(
		'user_id',
		'user_object',
		'display_name',
		'user_nicename',
		'user_email',
		'user_login'
	);

	/*
	fallback_defaults
	*/

	private $fallback_defaults=array(
		'query_type' => 'user_id',
		'format' => true,
		'zero' => '0 comments',
		'one' => '1 comment',
		'more' => '%c comments',
		'thousands_separator' => ',',
		'display' => true,
		'in_loop' => true
	);

	/*
	current defaults
	(merged database and fallback_defaults)
	*/

	private $defaults=array();

	/*
	fallback options
	*/

	private $fallback_options=array(
		'dashboard_widget' => false,
		'dashboard_widget_text' => 'You\'ve already written %c.',
		'dashboard_right_now' => false,
		'dashboard_right_now_text' => 'You\'ve already written %c.',

		'include_user_profile' => false,
		'user_profile_text' => 'You\'ve already written %c.',

		'include_user_admin' => true,

		'all_users_can_view_other_users_comment_counts' => true,
		'view_other_users_comment_counts_capability' => 'edit_users',

		'debug_mode' => false,

		'section' => 'dashboard'
	);

	/*
	current options
	(merged database and fallback_options)
	*/

	private $options=array();

	/*
	option-page sections/option-groups
	*/

	private $options_page_sections=array(
		'dashboard' => array(
			'nicename' => 'Dashboard',
			'callback' => 'dashboard',
			'fields' => array(
				'dashboard_widget' => 'Enable Dashboard Widget for all Users',
				'dashboard_widget_text' => 'Dashboard Widget Text (%c gets replaced with formatted comment count)',
				'dashboard_right_now' => 'Integrate in "Right Now" Box',
				'dashboard_right_now_text' => 'Integrate in "Right Now" Box Text (%c gets replaced with formatted comment count)'
			)
		),
		'user_profile' => array(
			'nicename' => 'User Profile Page',
			'callback' => 'user_profile',
			'fields' => array(
				'include_user_profile' => 'Display comment count on user profile page',
				'user_profile_text' => 'User Profile Page Text (%c gets replaced with formatted comment count)'
			)
		),
		'defaults' => array(
			'nicename' => 'Default Values',
			'callback' => 'defaults',
			'fields' => array(
				'query_type' => 'Query Type',
				'format' => 'Format',
				'zero' => 'Text 0 comments',
				'one' => 'Text 1 comment',
				'more' => 'Text more commments',
				'thousands_separator' => 'Thousands Separator',
				'display' => 'Display Results',
				'in_loop' => 'in Comment Loop'
			)
		),
		'administrative_options' => array(
			'nicename' => 'Administrative Options',
			'callback' => 'administrative_options',
			'fields' => array(
				'include_user_admin' => 'Display comment count on User page in Admin Menu',
				'all_users_can_view_other_users_comment_counts' => 'All users can view other users comment counts',
				'view_other_users_comment_counts_capability' => 'Capability to view comment count of other users',
				'debug_mode' => 'Enable Debug-Mode'
			)
		)
	);

	/*
	Constructor
	*/

	function __construct() {

		/*
		initialize object
		*/

		$this->set_plugin_url();
		$this->retrieve_settings();
		$this->register_scripts();
		$this->register_hooks();
	}

	/*
	register js libraries
	*/

	private function register_scripts() {
		wp_register_script($this->get_prefix().'utils', $this->get_plugin_url().'js/utils.js', array('prototype'), '1.00');

		wp_register_script($this->get_prefix().'settings_page', $this->get_plugin_url().'js/settings_page.js', array('prototype', $this->get_prefix().'utils'), '1.00');
	}

	/*
	register WordPress hooks
	*/

	private function register_hooks() {

		/*
		general
		*/

		add_filter('plugin_action_links', array(&$this, 'plugin_action_links'), 10, 2);

		add_action('admin_menu', array(&$this, 'admin_menu'));

		/*
		meta-data
		*/

		add_action('wp_head', array(&$this, 'head_meta'));
		add_action('admin_head', array(&$this, 'head_meta'));

		/*
		widgets
		*/

		add_action('widgets_init', array(&$this, 'widgets_init'));

		add_action('wp_dashboard_setup', array(&$this, 'add_dashboard_widget'));

		add_action('activity_box_end', array(&$this, 'add_right_now_box'));

		/*
		shortcodes
		*/

		add_shortcode($this->get_prefix().'count_by_user', array(&$this, 'shortcode_count_by_user'));

		add_shortcode($this->get_prefix().'count_by_comment', array(&$this, 'shortcode_count_by_comment'));

		/*
		profile_page
		*/

		add_action('show_user_profile', array(&$this, 'show_user_profile'));
		add_action('edit_user_profile', array(&$this, 'show_user_profile'));

		/*
		user panel in admin-menu
		*/

		if ($this->get_option('include_user_admin')) {
			add_filter('manage_users_columns', array(&$this, 'manage_users_columns'));
			add_filter('manage_users_custom_column', array(&$this, 'manage_users_custom_column'), 10, 3);

			/*
			we use the hook admin_head instead of
			admin_print_style because otherwise
			the style get overwritten by WordPress
			default settings

			@todo maybe remove in WP 3.1
			*/

			//add_action('admin_head-users.php', array(&$this, 'add_users_page_css'));
		}

		/*
		whitelist settings
		*/

		add_action('admin_init', array(&$this, 'admin_init'));
	}

	/*
	GETTERS AND SETTERS
	*/

	/*
	getter for prefix
	true with trailing _
	false without trailing _
	*/

	function get_prefix($trailing_=true) {
		if ($trailing_)
			return $this->prefix.'_';
		else
			return $this->prefix;
	}

	/*
	getter for nicename
	*/

	function get_nicename() {
		return $this->nicename;
	}

	/*
	setter for plugin_url
	*/

	private function set_plugin_url() {
		$this->plugin_url=plugins_url('', __FILE__).'/';
	}

	/*
	getter for plugin_url
	*/

	private function get_plugin_url() {
		return $this->plugin_url;
	}

	/*
	getter for default parameter
	*/

	private function get_default($param) {
		if (isset($this->defaults[$param]))
			return $this->defaults[$param];
		else
			return false;
	}

	/*
	getter for default parameter
	*/

	private function get_option($param) {
		if (isset($this->options[$param]))
			return $this->options[$param];
		else
			return false;
	}

	/*
	retrieve settings from database
	and merge with fallback-settings
	*/

	private function retrieve_settings() {
		$settings=get_option($this->get_prefix(false));

		/*
		did we retrieve an non-empty
		settings-array which we can
		merge with the default settings?
		*/

		if (!empty($settings) && is_array($settings)) {

			/*
			process options-array
			*/

			if (array_key_exists('options', $settings) && is_array($settings['options'])) {
				$this->options = array_merge($this->fallback_options, $settings['options']);
				$this->log('merging fallback-options '.var_export($this->fallback_options, true).' with database options '.var_export($settings['options'], true));
			}

			/*
			process defaults-array
			*/

			if (array_key_exists('defaults', $settings) && is_array($settings['defaults'])) {
				$this->defaults = array_merge($this->fallback_defaults, $settings['defaults']);
				$this->log('merging fallback-defaults '.var_export($this->fallback_defaults, true).' with database defaults '.var_export($settings['defaults'], true));
			}
		}

		/*
		for some strange reason,
		WordPress doesn't like to
		write to an empty option
		*/

		else {
			update_option($this->get_prefix(false), array());
		}

		/*
		if the settings have not been set
		we use the fallback-options array instead
		*/

		if (empty($this->options)) {
			$this->options = $this->fallback_options;
			$this->log('using fallback-options '.var_export($this->fallback_options, true));
		}

		/*
		if the settings have not been set
		we use the fallback-defaults array instead
		*/

		if (empty($this->defaults)) {
			$this->defaults = $this->fallback_defaults;
			$this->log('using fallback-defaults '.var_export($this->fallback_defaults, true));
		}

		$this->log('setting options to '.var_export($this->options, true));

		$this->log('setting defaults to '.var_export($this->defaults, true));
	}

	/*
	Sanitize and validate input
	Accepts an array, return a sanitized array
	*/

	function settings_validate($input) {

		/*
		we handle a reset call
		*/

		if (isset($input['reset'])) {
			return array(
				'defaults' => $this->fallback_defaults,
				'options' => $this->fallback_options
			);
		}

		/*
		check-fields are either 0 or 1
		*/

		$check_fields=array(
			'dashboard_widget',
			'dashboard_right_now',
			'include_user_profile',
			'format',
			'display',
			'in_loop',
			'include_user_admin',
			'all_users_can_view_other_users_comment_counts',
			'debug_mode'
		);

		foreach ($check_fields as $check_field) {
			$input[$check_field] = ($input[$check_field] == 1 ? true : false);
		}

		/*
		these text-fields should not be empty
		*/

		$text_fields=array(
			'zero',
			'one',
			'more',
			'user_profile_text',
			'dashboard_widget_text',
			'dashboard_right_now_text'
		);

		foreach ($text_fields as $text_field) {
			if (isset($input[$text_field]) && strlen($input[$text_field])<1)
				unset($input[$text_field]);
		}

		/*
		selected capabilities have to be
		within available capabilities
		*/

		$capability_fields=array(
			'all_user_can_view_other_users_comment_counts'
		);

		$capabilities=$this->get_all_capabilities();

		foreach ($capability_fields as $capability_field) {
			if (!in_array($input[$capability_field.'_capability'], $capabilities))
				unset($input[$capability_field.'_capability']);
		}

		/*
		include options
		*/

		$options=$this->fallback_options;

		foreach($options as $option => $value) {
			if (array_key_exists($option, $input))
				$options[$option]=$input[$option];
		}

		/*
		include defaults
		*/

		$defaults=$this->fallback_defaults;

		foreach($defaults as $default => $value) {
			if (array_key_exists($default, $input))
				$defaults[$default]=$input[$default];
		}

		$ret_val=array();

		$ret_val['defaults']=$defaults;
		$ret_val['options']=$options;

		return $ret_val;
	}

	/*
	merges parameter array with defaults array
	defaults-array can be changed with filter
	'featuring_countcomments_defaults'
	*/

	private function fill_default_parameters($params) {

		/*
		apply filter featuring_countcomments_defaults
		*/

		$filtered_defaults=apply_filters($this->get_prefix().'defaults', $this->defaults);

		/*
		merge filtered defaults with params
		params overwrite merged defaults
		*/

		return wp_parse_args($params, $filtered_defaults);
	}

	/*
	UTILITY FUNCTIONS
	*/

	/*
	checks if a value is an integer

	regex taken from php.net
	by mark at codedesigner dot nl
	*/

	private function is_integer($value) {
		return preg_match('@^[-]?[0-9]+$@', $value);
	}

	/*
	shows log messages on screen

	if debug_mode is set to true
	optionally execute trigger_error
	if we're handling an error
	*/

	private function log($message, $status=0) {
		$date_format = get_option('date_format');
		$time_format = get_option('time_format');

		$log_line=gmdate($date_format.' '.$time_format, current_time('timestamp')).' ';

		/*
		determine the log line's prefix
		*/

		if ($status==0)
			$log_line.='INFO';
		else if ($status==-1)
			$log_line.='<strong>ERROR</strong>';
		else if ($status==-2)
			$log_line.='WARNING';
		else if ($status==1)
			$log_line.='SQL';

		/*
		append message
		*/

		$log_line.=' '.$message.'<br />';

		/*
		output message to screen
		*/

		if ($this->get_option('debug_mode'))
			echo($log_line);

		/*
		output message to file
		*/

		if ($status<0)
			trigger_error($message);
	}

	/*
	retrieve user-id by looking up the users table,
	getting the currently logged in user or
	processing a WP_User object

	$params['user_attribute']: the user's attribute
	or empty to retrieve current user
	*/

	function get_user_id($params) {
		if (!is_array($params))
			throw new Exception('$params have to be an array!');

		global $wpdb;

		/*
		if the user_attribute is empty,
		we use the currently logged in user
		*/

		if (!isset($params['user_attribute'])) {
			$this->log('use currently logged in user');

			global $user_ID;

			/*
			load current user's details
			*/

			get_currentuserinfo();

			if (!$this->is_integer($user_ID) || $user_ID<1)
				throw new Exception('there is no user currently logged in');

			return $user_ID;
		}

		/*
		check pre-condition
		*/

		if (empty($params['query_type']))
			throw new Exception('query_type not defined');

		/*
		check if query_type is
		within the allowed attributes
		*/

		if (!in_array($params['query_type'], $this->search_attributes))
			throw new Exception('invalid query_type '.$params['query_type']);

		/*
		check for WP_User object
		*/

		if ($params['query_type']=='user_object') {

			/*
			unfortunately can't use instanceof
			because WP returns a standard-object
			instead of WP_User instance

			so we have to check if the
			user_id exists in this object
			*/

			if (!isset($params['user_attribute']->ID) || empty($params['user_attribute']->ID) || !$this->is_integer($params['user_attribute']->ID) || !$params['user_attribute']->ID>0)
				throw new Exception('user_object does not have the attribute ID');

			return $params['user_attribute']->ID;
		}


		/*
		use user_id
		*/

		else if ($params['query_type']=='user_id') {

			/*
			assure user_id is integer
			*/	

			$user_id=intval($params['user_attribute']);
			if (!$this->is_integer($user_id) || $user_id<1)
				throw new Exception('user_id '.$user_id.' is not in valid range');

			return $user_id;
		}

		/*
		query users table
		*/

		else {
			$q = "SELECT ".$wpdb->users.".ID FROM ".$wpdb->users." WHERE ".$wpdb->users.".".$params['query_type']." = '".$wpdb->escape($params['user_attribute'])."'";
			$this->log($q, 1);

			/*
			query for user-id
			*/

			$user_id = $wpdb->get_var($q);

			/*
			did we receive an id?
			*/

			if (empty($user_id) || !$this->is_integer($user_id) || $user_id<1)
				throw new Exception('the user with the attribute '.$params['query_type'].'='.$params['user_attribute'].' does not exist!');

			return $user_id;
		}
	}

	/*
	warns about deprecated functions
	*/

	function deprecated_function($function, $version, $replacement) {
		$this->log(sprintf( __('%1$s is <strong>deprecated</strong> since '.$this->get_nicename().' version %2$s! Use <strong>$'.$this->get_prefix(false).'->%3$s()</strong> instead.'), $function, $version, $replacement), -2);
	}

	/*
	returns all capabilities without 'level_'
	*/

	private function get_all_capabilities() {
		$wp_roles=new WP_Roles();
		$names=$wp_roles->get_names();

		$all_caps=array();

		foreach($names as $name_key => $name) {
			$wp_role=$wp_roles->get_role($name_key);
			$role_caps=$wp_role->capabilities;

			foreach($role_caps as $cap_key => $role_cap) {
				if (!in_array($cap_key, $all_caps) && strpos($cap_key, 'level_')===false)
					$all_caps[]=$cap_key;
			}
		}

		asort($all_caps);

		return $all_caps;
	}

	/*
	CALLED BY HOOKS
	(and therefore public)
	*/

	/*
	Options Page
	*/

	function options_page() {
		$this->settings_page($this->options_page_sections, 'manage_options', 'settings', true);
	}

	/*
	white list options
	*/

	function admin_init() {
		register_setting($this->get_prefix(false), $this->get_prefix(false), array(&$this, 'settings_validate'));

		$this->add_settings_sections($this->options_page_sections, 'settings');
	}

	/*
	add Featuring CountComments to WordPress Settings Menu

	we use the hook admin_head instead of admin_print_styles
	because otherwise the CSS-background for disabled
	input fields does not work
	*/

	function admin_menu() {
		$options_page=add_options_page($this->get_nicename(), $this->get_nicename(), 'manage_options', $this->get_prefix(false), array(&$this, 'options_page'));

		add_action('admin_print_scripts-'.$options_page, array(&$this, 'settings_print_scripts'));
		add_action('admin_head-'.$options_page, array(&$this, 'admin_styles'));
		add_contextual_help($options_page, $this->options_page_help());
	}

	/*
	adds meta-information to HTML header
	*/

	function head_meta() {
		echo("<meta name=\"".$this->get_nicename()."\" content=\"1.11\" />\n");
	}

	/*
	add dashboard widget
	*/

	function add_dashboard_widget() {
		if ($this->get_option('dashboard_widget'))
			wp_add_dashboard_widget($this->get_prefix().'dashboard_widget', $this->get_nicename(), array(&$this, 'dashboard_widget_output'));
	}

	/*
	dashboard widget
	*/

	function dashboard_widget_output() {
		$this->user_string('dashboard_widget');
	}

	/*
	add output to dashboard's right now box
	*/

	function add_right_now_box() {
		if ($this->get_option('dashboard_right_now')) {
			echo('<p></p>');

			$this->user_string('dashboard_right_now');
		}
	}

	/*
	embed comment count in user profile
	*/

	function show_user_profile($profileuser) {
		if ($this->get_option('include_user_profile')) {

			?><h3><?php echo($this->get_nicename()); ?></h3><?php

			$this->user_string('user_profile', $profileuser->ID);
		}
	}

	/*
	add additional comments column
	to User panel in Admin Menu
	*/

	function manage_users_columns($columns) {
		$columns['comments']='<div class="vers"><img alt="'.__('Comments').'" title="'.__('Comments').'" src="'.esc_url(admin_url('images/comment-grey-bubble.png')).'" /></div>';

		return $columns;
	}

	/*
	return comment-count to display in
	comments column in Admin Menu's User panel
	*/

	function manage_users_custom_column($unknown, $column_name, $user_id) {

		if ($column_name=='comments') {
			$unfiltered_params=array(
				'format' => true,
				'zero' => '0',
				'one' => '1',
				'more' => '%c'
			);

			$filtered_params=apply_filters($this->get_prefix().'users_custom_column', $unfiltered_params);

			$params=array(
				'user_attribute' => $user_id,
				'query_parameter' => 'user_id',
				'display' => false
			);

			$count=$this->count_by_user(array_merge($filtered_params, $params));

			if (!$this->is_integer($count) || $count<0)
				return '-';

			$user_object = new WP_User((int) $user_id);

			if (!is_object($user_object) || !$this->is_integer($user_object->ID) || $user_object->ID!=$user_id)
				return '-';

			/*
			include a link to edit-comments
			search functionality

			maybe someday update to query for
			user_id instead of display_name
			https://core.trac.wordpress.org/ticket/14163
			*/

			$ret_val='<div class="post-com-count-wrapper"><a class="post-com-count" href="'.add_query_arg(array('s' => urlencode($user_object->display_name)), admin_url('edit-comments.php')).'" title="'.htmlentities('Comments Search for "'.$user_object->display_name.'"', ENT_QUOTES, get_option('blog_charset'), false).'"><span class="comment-count">'.$count.'</span></a></div>';

			return $ret_val;
		}
	}

	/*
	adds some CSS to format the
	Featuring CountComments column
	on users.php

	@todo maybe remove in WP 3.1
	*/

	function add_users_page_css() { ?>
		<style type="text/css">
			th.column-<?php echo($this->get_prefix(false)); ?>, td.column-<?php echo($this->get_prefix(false)); ?> {
				text-align:center;
			}
		</style>
	<?php }

	/*
	called from widget_init hook
	*/

	function widgets_init() {
		register_widget('WP_Widget_'.str_replace(' ', '', $this->get_nicename()));
	}

	/*
	loads the necessary java-scripts
	for the options-page
	*/

	function settings_print_scripts() {
		wp_enqueue_script($this->get_prefix().'settings_page');
	}

	/*
	adds a settings link in the plugin-tab
	*/

	function plugin_action_links($links, $file) {
		if ($file == plugin_basename(__FILE__))
			$links[] = '<a href="options-general.php?page='.$this->get_prefix(false).'">' . __('Settings') . '</a>';

		return $links;
	}

	/*
	loads the necessary CSS-styles
	for the admin-page
	*/

	function admin_styles() { ?>

	<style type="text/css">

			.<?php echo($this->get_prefix()); ?>wrap ul {
				list-style-type : disc;
				padding: 5px 5px 5px 30px;
			}

			ul.subsubsub.<?php echo($this->get_prefix(false)); ?> {
				list-style: none;
				margin: 8px 0 5px;
				padding: 0;
				white-space: nowrap;
				float: none;
				display: block;
			}
 
			ul.subsubsub.<?php echo($this->get_prefix(false)); ?> a {
				line-height: 2;
				padding: .2em;
				text-decoration: none;
			}

			ul.subsubsub.<?php echo($this->get_prefix(false)); ?> li {
				display: inline;
				margin: 0;
				padding: 0;
				border-left: 1px solid #ccc;
				padding: 0 .5em;
			}

			ul.subsubsub.<?php echo($this->get_prefix(false)); ?> li:first-child {
				padding-left: 0;
				border-left: none;
			}

 			input[disabled], input[disabled='disabled'] {
				background: #EEE;
			}

	</style>

	<?php }

	/*
	LOGIC FUNCTIONS
	*/

	/*
	internal function of count_by_user
	*/

	private function _by_user($params) {

		/*
		log call
		*/

		$this->log('function _by_user, $params='.var_export($params, true));

		/*
		fill params with default-values
		*/

		$params=$this->fill_default_parameters($params);

		/*
		retrieve user_id
		*/

		$params['user_id']=$this->get_user_id($params);

		global $user_ID;

		/*
		load current user's details
		*/

		get_currentuserinfo();

		/*
		if a user tries to
		view the comment count of
		another user, we conduct
		a security check
		*/

		if ($params['user_id']!=$user_ID && !$this->get_option('all_users_can_view_other_users_comment_counts') && !current_user_can($this->get_option('view_other_users_comment_counts_capability')))
			throw new Exception('You are not authorized to view another user\'s comment counts!');

		/*
		validate user_id
		*/

		if (!isset($params['user_id']) || !$this->is_integer($params['user_id']) || $params['user_id']<1 || !get_userdata($params['user_id']))
			throw new Exception('user_id '.$params['user_id'].' does not exist');

		/*
		retrieve the cached comment-count
		for the the given user-id
		*/

		$count=$this->get_cached_user_count($params['user_id']);

		/*
		formatted comment count
		*/

		if ($params['format'])
			$ret_val=$this->format_comment_count($count, $params);

		/*
		plain comment count
		*/

		else
			$ret_val=$count;

		/*
		echo results
		*/

		if ($params['display'])
			echo($ret_val);

		/*
		return result
		*/

		else
			return $ret_val;
	}

	/*
	internal function of count_by_comment
	*/

	private function _by_comment($params) {

		/*
		log call
		*/

		$this->log('function _by_comment, $params='.var_export($params, true));

		/*
		fill params with default-values
		*/

		$params=$this->fill_default_parameters($params);

		/*
		gets comment
		empty($params['comment']) means
		to retrieve the current comment
		in the comment loop
		*/

		if (!isset($params['comment']))
			$params['comment']=null;

		$comment=get_comment($params['comment']);

		/*
		check pre-conditions
		*/

		if (empty($comment))
			throw new Exception('not in comment loop or comment not found');

		if (!isset($comment->user_id) || empty($comment->user_id) || !$this->is_integer($comment->user_id) || $comment->user_id<1)
			throw new Exception('user_id does not exist for comment');

		$this->log('retrieved comment object: '.var_export($comment, true));

		/*
		we don't need the comment
		in the params anymore
		*/

		unset($params['comment']);

		/*
		set attribute to comment's user_id and
		query_type to 'user_id'
		*/

		$params['user_attribute']=$comment->user_id;
		$params['query_type']='user_id';

		/*
		are we handling a comment loop?
		did we already cache the related user's comment count?
		*/

		if ($params['in_loop'] && !isset($this->cache[$params['user_attribute']])) {

			/*
			cache all counts for current post
			to decrease the number of database queries
			*/

			if (!$this->precache_post_counts($comment->comment_post_ID))
				$this->log('something went wrong while caching the count for comment '.$comment->ID.' of post '.$comment_post_ID, -2);
		}

		return $this->_by_user($params);
	}

	/*
	cache comment counts for all users
	who commented on a certain post
	*/

	private function precache_post_counts($post_ID) {
		global $wpdb;

		$this->log('precache_post_counts for post_id '.$post_ID);

		/*
		check pre-condition
		*/

		if (empty($post_ID))
			return false;

		/*
		assure post_ID is integer
		*/

		$post_ID=intval($post_ID);
		if (!$this->is_integer($post_ID) || $post_ID<1)
			return false;

		/*
		get all commenters for current post
		*/

		$q = "SELECT DISTINCT $wpdb->comments.user_id FROM $wpdb->comments WHERE $wpdb->comments.comment_post_ID = $post_ID AND $wpdb->comments.user_id <> ''";

		$this->log($q, 1);

		$users = $wpdb->get_col($q);

		/*
		did we receive any commenters?
		*/

		if (empty($users))
			return false;

		$aq='';

		/*
		prepare SQL statement's where-clause
		and reset retrieved commenter's counts
		*/

		foreach ($users as $user) {
			if ($this->is_integer($user)) {
				$this->cache[$user] = 0;
				$aq.="'".$wpdb->escape($user)."', ";
			}
		}

		/*
		check SQL pre-condition
		*/

		if (strlen($aq)<1)
			return false;

		/*
		remove trailing ", "
		*/

		$aq=substr($aq, 0, -2);

		/*
		get comment counts
		*/

		$q = "SELECT $wpdb->comments.user_id, COUNT($wpdb->comments.comment_ID) AS count FROM $wpdb->comments WHERE $wpdb->comments.comment_approved = '1' and $wpdb->comments.user_id in ( $aq ) GROUP BY $wpdb->comments.user_id";

		$this->log($q, 1);

		$rows = $wpdb->get_results($q);

		/*
		did we receive any results?
		*/

		if (empty($rows))
			return false;

		/*
		store results in cache
		*/

		foreach ($rows as $row)
			$this->cache[$row->user_id] = $row->count;

		return true;
	}

	/*
	cache comment count for selected user
	*/

	private function get_cached_user_count($user_id) {
		global $wpdb;

		if (!$this->is_integer($user_id))
			return 0;

		/*
		did we already cache this user?
		*/

		if (!isset($this->cache[$user_id])) {
			$this->log('cache user count for user_id '.$user_id);

			$q = "SELECT COUNT($wpdb->comments.comment_ID) FROM $wpdb->comments WHERE $wpdb->comments.comment_approved = '1' AND $wpdb->comments.user_id = $user_id";

			$this->log($q, 1);

			/*
			get comment count for user
			*/

			$count = $wpdb->get_var($q);

			/*
			store results in cache
			*/

			if ($count!==null) {
				$this->cache[$user_id] = $count;
				return $count;
			}

			/*
			no results - maybe user deleted account
			*/

			else
				throw new Exception('comment count could not be retrieved for user-id '.$user_id);
		}

		/*
		return cached results
		*/

		else {
			$this->log('use user cache for user_id '.$user_id);
			return $this->cache[$user_id];
		}
	}

	/*
	echoes a formatted version of a comment count
	*/

	private function format_comment_count($count, $params) {

		/*
		check pre-condition
		*/

		$text_fields=array(
			'zero',
			'one',
			'more'
		);

		foreach ($text_fields as $text_field) {
			if (!isset($params[$text_field]) || strlen($params[$text_field])<1) {
				$this->log('the format attribute for '.$text_field.' has not been set; using defaults', -2);
				$params[$text_field]=$this->fallback_defaults[$text_field];
			}
		}

		/*
		format count
		*/

		$formatted_count=number_format($count, 0, '', $params['thousands_separator']);

		if ($count == 0)
			return str_replace('%c', $formatted_count, $params['zero']);
		else if ($count == 1)
			return str_replace('%c', $formatted_count, $params['one']);
		else if($count > 1)
			return str_replace('%c', $formatted_count, $params['more']);
		else
			throw new Exception('General Error!');
	}

	/*
	output current user's
	formatted comment count
	*/

	private function user_string($filter, $user_id=null) {
		$filtered_params=apply_filters($this->get_prefix().$filter, array());

		$params=array(
			'user_attribute' => $user_id,
			'query_type' => 'user_id',
			'format' => true,
			'display' => false
		);

		/*
		retrieve formatted comment-count
		for currently logged in user
		*/

		$formatted_comment_count=$this->count_by_user(array_merge($filtered_params, $params));

		/*
		replace %c in option
		and output the result
		*/

		$filtered_string=apply_filters($this->get_prefix().$filter.'_text', $this->get_option($filter.'_text'));

		echo('<p>'.str_replace('%c', $formatted_comment_count, $filtered_string).'</p>');
	}

	/*
	ADMIN MENU - UTILITY
	*/

	/*
	register settings sections and fields
	*/

	private function add_settings_sections($settings_sections, $section_prefix) {

		/*
		settings-sections
		*/

		foreach($settings_sections as $section_key => $section) {
			$this->add_settings_section($section_key, $section['nicename'], $section_prefix, $section['callback']);

			/*
			fields for each section
			*/

			if (array_key_exists('fields', $section)) {
				foreach ($section['fields'] as $field_key => $field) {
					$this->add_settings_field($field_key, $field, $section_key, $section_prefix);
				}
			}
		}
	}

	/*
	adds a settings section
	*/

	private function add_settings_section($section_key, $section_name, $section_prefix, $callback) {
		add_settings_section('default', $section_name, array(&$this, 'callback_'.$section_prefix.'_'.$callback), $this->get_prefix().$section_prefix.'_'.$section_key);
	}

	/*
	adds a settings field
	*/

	private function add_settings_field($field_key, $field_name, $section_key, $section_prefix, $label_for='') {
		if (empty($label_for))
			$label_for=$this->get_prefix().$field_key;

		add_settings_field($this->get_prefix().$field_key, $field_name, array(&$this, 'setting_'.$field_key), $this->get_prefix().$section_prefix.'_'.$section_key, 'default', array('label_for' => $label_for));
	}

	/*
	creates section link
	*/

	private function get_section_link($sections, $section, $section_nicename='', $create_id=false) {
		if (strlen($section_nicename)<1)
			$section_nicename=$sections[$section]['nicename'];

		$id='';
		if ($create_id)
			$id=' id="'.$this->get_prefix().$section.'_link"';

		$menuitem_onclick=" onclick=\"".$this->get_prefix()."open_section('".$section."');\"";

		return '<a'.$id.$menuitem_onclick.' href="javascript:void(0);">'.$section_nicename.'</a>';
	}

	/*
	returns name="featuring_countcomments[setting]" id="featuring_countcomments_setting"
	*/

	private function get_setting_name_and_id($setting) {
		return 'name="'.$this->get_prefix(false).'['.$setting.']" id="'.$this->get_prefix().$setting.'"';
	}

	/*
	returns default value for option-field
	*/

	private function get_setting_default_value($field, $type) {
		$default_value=null;

		if ($type=='options')
			$default_value=htmlentities($this->get_option($field), ENT_QUOTES, get_option('blog_charset'), false);
		else if ($type=='defaults')
			$default_value=htmlentities($this->get_default($field), ENT_QUOTES, get_option('blog_charset'), false);
		else
			throw new Exception('type '.$type.' does not exist for field '.$field.'!');

		return $default_value;
	}

	/*
	outputs a settings section
	*/

	private function do_settings_sections($section_key, $section_prefix) {
		do_settings_sections($this->get_prefix().$section_prefix.'_'.$section_key);
	}

	/*
	Settings Page
	*/

	private function settings_page($settings_sections, $permissions, $section_prefix, $is_wp_options) {

		/*
		security check
		*/

		if (!current_user_can($permissions))
			wp_die(__('You do not have sufficient permissions to display this page.'));

		/*
		option-page html
		*/

		?><div class="wrap">
		<?php if (function_exists('screen_icon')) screen_icon(); ?>
		<h2><?php echo($this->get_nicename()); ?></h2>

		<?php call_user_func(array(&$this, 'callback_'.$section_prefix.'_intro')); ?>

		<div id="<?php echo($this->get_prefix()); ?>menu" style="display:none"><ul class="subsubsub <?php echo($this->get_prefix(false)); ?>">
		<?php

		$menu='';

		foreach ($settings_sections as $key => $section)
			$menu.='<li>'.$this->get_section_link($settings_sections, $key, '', true).'</li>';

		echo($menu);
		?>
		</ul></div>

		<div class="<?php echo($this->get_prefix()); ?>wrap">

		<?php if ($is_wp_options) { ?>
			<form method="post" action="<?php echo(admin_url('options.php')); ?>">
			<?php settings_fields($this->get_prefix(false));
		}

		foreach ($settings_sections as $key => $section) {

		?><div id="<?php echo($this->get_prefix().$key); ?>"><?php

			$this->do_settings_sections($key, $section_prefix);
			echo('</div>');
		}

		?>

		<?php if ($is_wp_options) { ?>
			<p class="submit">
			<?php
			$submit_buttons=array(
				'submit' => 'Save Changes',
				'reset' => 'Use Defaults'
			);

			foreach ($submit_buttons as $key => $submit_button)
				$this->setting_submit_button($key, $submit_button);
			?>
			</p>
			</form>
		<?php } ?>

		<?php $this->neotrinity_support(); ?>

		</div>

		</div>

		<?php /*
		JAVASCRIPT
		*/ ?>

		<?php $this->settings_page_js($settings_sections); ?>

	<?php }

	/*
	settings pages's javascript
	*/

	private function settings_page_js($settings_sections) { ?>

	<script type="text/javascript">

	/* <![CDATA[ */

	/*
	section-divs
	*/

	var <?php echo($this->get_prefix()); ?>sections = [<?php

	$available_sections=array();

	foreach($settings_sections as $key => $section)
		array_push($available_sections, '"'.$key.'"');

	echo(implode(',', $available_sections));
	?>];

	var section=$('<?php echo($this->get_prefix()); ?>section').value;
	if (!section)
		section='';

	<?php echo($this->get_prefix()); ?>open_section(section);

	/*
	display js-menu
	if js has been disabled,
	the menu will not be visible
	*/

	$('<?php echo($this->get_prefix()); ?>menu').style.display="block";

	/* ]]> */

	</script>

	<?php }

	/*
	ADMIN MENU - COMPONENTS
	*/

	/*
	generic checkbox
	*/

	private function setting_checkfield($name, $type, $related_fields=array(), $js_checked=true) {

		$javascript_onclick_related_fields='';

		/*
		build javascript function
		to enable/disable related fields
		*/

		if (!empty($related_fields)) {

			/*
			prepare for javascript array
			*/

			foreach($related_fields as &$related_field)
				$related_field='\''.$related_field.'\'';

			/*
			build onclick-js-call
			*/

			$javascript_toggle=$this->get_prefix().'toggle_related_fields(';

			$javascript_fields=', ['.implode(', ', $related_fields).']';

			/*
			check for disabled fields
			on onload event
			*/

			?>

			<script type="text/javascript">

			/* <![CDATA[ */

			Event.observe(window, 'load', function(e){ <?php echo($javascript_toggle.'$(\''.$this->get_prefix().$name.'\')'.$javascript_fields. ', '.($js_checked == 1 ? '1' : '0').');'); ?> });

			/* ]]> */

			</script>

			<?php

			/*
			build trigger for settings_field
			*/

			$javascript_onclick_related_fields='onclick="'.$javascript_toggle.'this'.$javascript_fields. ', '.($js_checked == 1 ? '1' : '0').');"';
		}

		$checked=$this->get_setting_default_value($name, $type); ?>
		<input <?php echo($this->get_setting_name_and_id($name)); ?> type="checkbox" <?php echo($javascript_onclick_related_fields); ?> value="1" <?php checked('1', $checked); ?> />
	<?php }

	/*
	generic textinput
	*/

	private function setting_textfield($name, $type, $size=30, $javascript_validate='') {
		$default_value=$this->get_setting_default_value($name, $type); ?>
		<input type="text" <?php echo($this->get_setting_name_and_id($name).' '.$javascript_validate); ?> maxlength="<?php echo($size); ?>" size="<?php echo($size); ?>" value="<?php echo $default_value; ?>" />
	<?php }

	/*
	generic submit-button
	*/

	private function setting_submit_button($field_key, $button) { ?>
		<input type="submit" name="<?php echo($this->get_prefix(false)); ?>[<?php echo($field_key); ?>]" id="<?php echo($this->get_prefix(false)); ?>_<?php echo($field_key); ?>" class="button-primary" value="<?php _e($button) ?>" />
	<?php }

	/*
	generic capability select
	*/

	private function setting_capability($name, $type) {
		?><select <?php echo($this->get_setting_name_and_id($name.'_capability')); ?>>

			<?php
			$capabilities=$this->get_all_capabilities();

			$ret_val='';

			foreach ($capabilities as $capability) {
				$_selected = $capability == $this->get_setting_default_value($name.'_capability', $type) ? " selected='selected'" : '';
				$ret_val.="\t<option value='".$capability."'".$_selected.">" . $capability . "</option>\n";
			}

			echo $ret_val;
			?>

		</select><?php
	}

	/*
	outputs support paragraph
	*/

	private function neotrinity_support() { ?>
		<h3>Support</h3>
		If you like to support the development of <?php echo($this->get_nicename()); ?>, you can invite me for a <a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&amp;business=bernhard%40riedl%2ename&amp;item_name=Donation%20for%20Featuring%20CountComments&amp;no_shipping=1&amp;no_note=1&amp;tax=0&amp;currency_code=EUR&amp;bn=PP%2dDonationsBF&amp;charset=UTF%2d8">virtual pizza</a> for my work. <?php echo(convert_smilies(':)')); ?><br /><br />

		<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_xclick" /><input type="hidden" name="business" value="&#110;&#101;&#111;&#64;&#x6E;&#x65;&#x6F;&#x74;&#x72;&#105;&#110;&#x69;&#x74;&#x79;&#x2E;&#x61;t" /><input type="hidden" name="item_name" value="Donation for Featuring CountComments" /><input type="hidden" name="no_shipping" value="2" /><input type="hidden" name="no_note" value="1" /><input type="hidden" name="currency_code" value="EUR" /><input type="hidden" name="tax" value="0" /><input type="hidden" name="bn" value="PP-DonationsBF" /><input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" style="border:0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" /><img alt="If you like to, you can support me." src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" /></form><br />

		Maybe you also want to <?php if (current_user_can('manage_links')) { ?><a href="link-add.php"><?php } ?>add a link<?php if (current_user_can('manage_links')) { ?></a><?php } ?> to <a href="http://www.neotrinity.at/projects/">http://www.neotrinity.at/projects/</a>.<br /><br />
	<?php }

	/*
	ADMIN MENU - SECTIONS + HELP
	*/

	/*
	intro callback
	*/

	function callback_settings_intro() {
		$this->check_registration_status(); ?>	Welcome to the Settings-Page of <a target="_blank" href="http://www.neotrinity.at/projects/"><?php echo($this->get_nicename()); ?></a>. This plugin counts the number of comments for each user who has been logged in at the time of commenting.
	<?php }

	/*
	check registration status for commenting
	*/

	private function check_registration_status() {
		$comment_registration=get_option('comment_registration');

		if (empty($comment_registration))
			echo ('<div class="updated"><strong>'.'Warning!</strong> You\'ve currently turned of that <strong>users must be registered and logged in to comment</strong>. - In order to make '.$this->get_nicename().' work properly you must <a href="'.admin_url( 'options-discussion.php' ).'">turn this setting on</a>.'.'</div>');
	}

	/*
	adds help-text to admin-menu contextual help
	*/

	function options_page_help() {
		return "<div class=\"".$this->get_prefix()."wrap\"><ul>

			<li>You can display the comment count of the user who is currently logged in, by adding a <a href=\"widgets.php\">Sidebar Widget</a>, enabling the ".$this->get_section_link($this->options_page_sections, 'dashboard', 'Dashboard Widget')." or displaying it on the ".$this->get_section_link($this->options_page_sections, 'user_profile', 'user\'s profile page').".</li>

			<li><a target=\"_blank\" href=\"http://wordpress.org/extend/plugins/featuring-countcomments/other_notes/\">Geek stuff</a>: You can output the comment counts of your users by calling the <abbr title=\"PHP: Hypertext Preprocessor\">PHP</abbr> function <code>$".$this->get_prefix(false)."->count_by_user(\$params)</code> or <code>$".$this->get_prefix(false)."->count_by_comment(\$params)</code> wherever you like (don't forget <code>global $".$this->get_prefix(false)."</code>). These functions can also be invoked by the usage of shortcodes. The default values for these function can be set in the ".$this->get_section_link($this->options_page_sections, 'defaults', 'Default Values Section').", by using <a target=\"_blank\" href=\"http://wordpress.org/extend/plugins/featuring-countcomments/other_notes/\">WordPress filters</a>, or directly in the <abbr title=\"PHP: Hypertext Preprocessor\">PHP</abbr> function/WordPress shortcode calls.</li>

			<li>If you decide to uninstall ".$this->get_nicename().", firstly remove the optionally added <a href=\"widgets.php\">Sidebar Widget</a>, integrated <abbr title=\"PHP: Hypertext Preprocessor\">PHP</abbr> function or WordPress shortcode call(s). Afterwards, disable and delete ".$this->get_nicename()." in the <a href=\"plugins.php\">Plugins Tab</a>.</li>

			<li><strong>For more information:</strong><br /><a target=\"_blank\" href=\"http://wordpress.org/extend/plugins/".str_replace('_', '-', $this->get_prefix(false))."/\">".$this->get_nicename()." in the WordPress Plugin Directory</a></li>

		</ul></div>";
	}


	/*
	section dashboard
	*/

	function callback_settings_dashboard() { ?>
		If you enable one of the next options, <?php echo($this->get_nicename()); ?> will show the comment count of the currently logged in user either as a <a href="index.php">Dashboard Widget</a> or in the Right-Now-Box on the <a href="index.php">Dashboard</a>.
	<?php }

	function setting_dashboard_widget($params=array()) {
		$this->setting_checkfield('dashboard_widget', 'options', array('dashboard_widget_text'));
	}

	function setting_dashboard_widget_text($params=array()) {
		$this->setting_textfield('dashboard_widget_text', 'options', 100);
	}

	function setting_dashboard_right_now($params=array()) {
		$this->setting_checkfield('dashboard_right_now', 'options', array('dashboard_right_now_text'));
	}

	function setting_dashboard_right_now_text($params=array()) {
		$this->setting_textfield('dashboard_right_now_text', 'options', 100);
	}

	/*
	section user-profile
	*/

	function callback_settings_user_profile() { ?>
		If you enable the next option, <?php echo($this->get_nicename()); ?> will show the comment count of the currently logged on the user's <a href="profile.php">profile page</a>.
	<?php }

	function setting_include_user_profile($params=array()) {
		$this->setting_checkfield('include_user_profile', 'options', array('user_profile_text'));
	}

	function setting_user_profile_text($params=array()) {
		$this->setting_textfield('user_profile_text', 'options', 100);
	}

	/*
	section defaults
	*/

	function callback_settings_defaults() { ?>
		In this section you can modify the defaults settings of <?php echo($this->get_nicename()); ?>.

		<ul>

			<li>You can select the default <em>Query Type</em> and whether you want to <em>Display</em> the results.</li>

			<li>If you select to <em>Format</em> the results, you can choose how the formatted string should look like in the fields <em>Text 0 comments</em>, <em>Text 1 comments</em> and <em>Text more comments</em>. <em>%c</em> will be replaced with the user's comment count. Moreover you can choose the format of the <em>Thousands Separator</em>.</li>

			<li><em>Display Results</em> only refers to direct function calls with <code>$<?php echo($this->get_prefix(false)); ?>->count_by_user($params)</code> or <code>$<?php echo($this->get_prefix(false)); ?>->count_by_comment($params)</code>.</li>

			<li>The last option, <em>in Comment Loop</em>, refers only to the function <code>$<?php echo($this->get_prefix(false)); ?>->count_by_comment($params)</code>. If this function/shortcode is invoked and the option is selected, the comment count of all users who posted a comment on a certain post will be retrieved. This saves time on posts with more than one comment.</li>

		</ul>
	<?php }

	function setting_query_type($params=array()) { ?>

		<select <?php echo($this->get_setting_name_and_id('query_type')); ?>>

			<?php

			foreach ($this->search_attributes as $search_attribute) {
				$_selected = $search_attribute == $this->get_default('query_type') ? " selected='selected'" : '';
				$ret_val.="\t<option value='".$search_attribute."'".$_selected.">" . $search_attribute . "</option>\n";
			}

			echo $ret_val;
			?>

		</select>
	<?php }

	function setting_format($params=array()) {
		$this->setting_checkfield('format', 'defaults', array('zero', 'one', 'more', 'thousands_separator'));
	}

	function setting_zero($params=array()) {
		$this->setting_textfield('zero', 'defaults');
	}

	function setting_one($params=array()) {
		$this->setting_textfield('one', 'defaults');
	}

	function setting_more($params=array()) {
		$this->setting_textfield('more', 'defaults');
	}

	function setting_thousands_separator($params=array()) {
		$this->setting_textfield('thousands_separator', 'defaults', 1);
	}

	function setting_display($params=array()) {
		$this->setting_checkfield('display', 'defaults');
	}

	function setting_in_loop($params=array()) {
		$this->setting_checkfield('in_loop', 'defaults');
	}

	/*
	section administrative options
	(also holds hidden section id)
	*/

	function callback_settings_administrative_options() { ?>
		<ul>
			<li>If you select <em>Display comment count on User page in Admin Menu</em>, every user's comment count will be displayed on the <a href="users.php">users-page</a>.</li>

			<li>If you want to keep the comment counts as a secret, you can deactivate <em>All users can view other users comment counts</em>. In that case, only users with the <em><a target="_blank" href="http://codex.wordpress.org/Roles_and_Capabilities">Capability</a> to view comment count of other users</em> can access this information.</li>

			<li>The <em>Debug Mode</em> can be used to have a look on the actions undertaken by <?php echo($this->get_nicename()); ?> and to investigate unexpected behaviour.</li>
		</ul>

		<input type="hidden" <?php echo($this->get_setting_name_and_id('section')); ?> value="<?php echo($this->get_option('section')); ?>" />
	<?php }

	function setting_include_user_admin($params=array()) {
		$this->setting_checkfield('include_user_admin', 'options');
	}

	function setting_all_users_can_view_other_users_comment_counts($params=array()) {
		$this->setting_checkfield('all_users_can_view_other_users_comment_counts', 'options', array('view_other_users_comment_counts_capability'), false);
	}

	function setting_view_other_users_comment_counts_capability($params=array()) {
		$this->setting_capability('view_other_users_comment_counts', 'options');
	}

	function setting_debug_mode($params=array()) {
		$this->setting_checkfield('debug_mode', 'options');
	}

	/*
	TESTS
	*/

	function execute_tests($selected_test_set=null) {
		$this->options['debug_mode']=true;

		$function_test_sets=array(

			/*
			function 'count_by_user'
			*/

			'count_by_user' => array(

			/*
			default:
			- current user (no params)
			- user 1
			- user 2
			- user 1 as object
			*/

			array('description' => 'current user', 'params' => array()),
			array('description' => 'user 1', 'params' => array('user_attribute' => 1)),
			array('description' => 'user 2', 'params' => array('user_attribute' => 2)),
			array('description' => 'user 1 as object', 'params' => array('user_attribute' => get_userdata(2), 'query_type' => 'user_object')),

			/*
			error:
			- user -1
			- user 0
			- user 100
			- user 'a'
			*/

			array('description' => 'error user -1', 'params' => array('user_attribute' => -1)),
			array('description' => 'error user 0', 'params' => array('user_attribute' => 0)),
			array('description' => 'error user 100', 'params' => array('user_attribute' => 100)),
			array('description' => 'error user a', 'params' => array('user_attribute' => 'a')),
			array('description' => 'wrong object', 'params' => array('user_attribute' => new FeaturingCountComments(), 'query_type' => 'user_object')),

			/*
			test user_name
			*/

			/*
			default:
			- user 'admin' / default=display_name
			- user 'test' / default=display_name
			- user 'admin' / user_nicename
			- user 'admin' / user_email
			- user 'admin' / user_login
			*/

			array('description' => 'user admin via display_name', 'params' => array('user_attribute' => 'admin', 'query_type' => 'display_name')),
			array('description' => 'user test via display_name', 'params' => array('user_attribute' => 'test', 'query_type' => 'display_name')),
			array('description' => 'user admin via user_nicename', 'params' => array('user_attribute' => 'admin', 'query_type' => 'user_nicename')),
			array('description' => 'user admin via user_email', 'params' => array('user_attribute' => 'test@test.com', 'query_type' => 'user_email')),
			array('description' => 'user admin via user_login', 'params' => array('user_attribute' => 'admin', 'query_type' => 'user_login')),

			/*
			error:
			- user_attribute ''
			- user_attribute 'bogus'
			- user_attribute 'admin', query_type 'bogus'
			*/

			array('description' => 'error no user_attribute', 'params' => array('user_attribute' => '')),
			array('description' => 'error user_attribute bogus', 'params' => array('user_attribute' => 'bogus')),
			array('description' => 'error user_attribute admin, query_type bogus', 'params' => array('user_attribute' => 'admin', 'query_type' => 'bogus'))

			),

			/*
			function count_by_comment
			*/

			'count_by_comment' => array(

			/*
			default:
			- current comment
			- comment 2
			- comment 5
			*/

			array('description' => 'current comment', 'params' => array()),
			array('description' => 'comment 2', 'params' => array('comment' => 2)),
			array('description' => 'comment 5', 'params' => array('comment' => 5)),

			/*
			error:
			- comment 1
			- comment 100
			- comment 'bogus'
			- comment ''
			*/

			array('description' => 'error comment 1', 'params' => array('comment' => 1)),
			array('description' => 'error comment 100', 'params' => array('comment' => 100)),
			array('description' => 'error comment bogus', 'params' => array('comment' => 'bogus')),
			array('description' => 'error comment empty', 'params' => array('comment' => ''))

			)
		);

		foreach ($function_test_sets as $function => $function_test_set) {
			if (!empty($selected_test_set) && $function==$selected_test_set || empty($selected_test_set))
				foreach ($function_test_set as $function_test) {
					echo($function_test['description'].': ');
					call_user_func(array(&$this, $function), $function_test['params']);
					echo('<br />');
				}
		}

		$this->options['debug_mode']=false;
	}

	/*
	API FUNCTIONS
	*/

	/*
	Counts the number of comments made
	by a user who is currently logged or
	has a particular attribute

	$params:

	- `user_attribute`: one of the user's attributes (matching `query_type`), for example, the user_id or a WP_User object; if no user_attribute is given, will fallback to currently logged in user

	- `query_type`: corresponding sql-field of user's attribute or WP_User object; default is `user_id`

		- user_id
		- display_name
		- user_nicename
		- user_email
		- user_login
 		- WP_User object

	- `format`: if set to true (default), the output will be formatted using the attributes `zero`, `one`, `more` and `thousands_separator`; false = process plain count value

	- `zero`, `one`, `more`: for formatted output - %c gets replaced 	with the number of comments

		'zero' => '0 comments'
		'one' => '1 comment'
		'more' => '%c comments'

	- `thousands_separator`: divides counts by thousand delimiters; default `,` => e.g. 1,386

	- `display`: if you want to return the count (e.g. for storing it in a variable) instead of echoing it with this function-call, set `display` to `false`; default setting is `true`
	*/

	function count_by_user($params=array()) {
		try {
			return $this->_by_user($params);
		}
		catch(Exception $e) {
			$this->log($e->getMessage(), -1);
			return false;
		}
	}

	/*
	Counts the number of comments made
	by a user who wrote a certain comment or
	the current comment in the comment-loop

	$params:

	- `comment`: a comment object or comment id; if empty 	retrieves current comment

	- `format`: if set to true (default), the output will be formatted using the attributes `zero`, `one`, `more` and `thousands_separator`; false = process plain count value

	- `zero`, `one`, `more`: for formatted output - %c gets replaced 	with the number of comments

		- 'zero' => '0 comments'
		- 'one' => '1 comment'
		- 'more' => '%c comments'

	- `thousands_separator`: divides counts by thousand delimiters default `,` => e.g. 1,386

	- `display`: if you want to return the count (e.g. for storing it in a variable) instead of echoing it with this function-call, set `display` to `false`; default setting is `true`

	- `in_loop`: if set to true (default), the query count for all user who wrote a comment which belongs to the post of the handed over `comment` will be cached; otherwise the comment count will be retrieved only for the user who posted the `comment`
	*/

	function count_by_comment($params=array()) {
		try {
			return $this->_by_comment($params);
		}
		catch(Exception $e) {
			$this->log($e->getMessage(), -1);
			return false;
		}
	}

	/*
	SHORTCODES
	*/

	/*
	shortcode for function output
	*/

	function shortcode_count_by_user($params, $content=null) {
		$params['display']=false;

		return $this->count_by_user($params);
	}

	/*
	shortcode for function count_by_comment
	*/

	function shortcode_count_by_comment($params, $content=null) {
		$params['display']=false;

		return $this->count_by_comment($params);
	}

}

/*
WIDGET CLASS
*/

class WP_Widget_FeaturingCountComments extends WP_Widget {

	/*
	constructor
	*/

	function WP_Widget_FeaturingCountComments () {
		global $featuring_countcomments;

		$widget_ops = array(
			'classname' => 'widget_'.$featuring_countcomments->get_prefix(false),
			'description' => 'Counts the number of comments by the currently logged in user.'
		);

		$this->WP_Widget($featuring_countcomments->get_prefix(false), $featuring_countcomments->get_nicename(), $widget_ops);
	}

	/*
	produces the widget-output
	*/

	function widget($args, $instance) {
		global $featuring_countcomments;

		extract($args);

		$title = !isset($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
		$text = !isset($instance['text']) ? 'You\'ve already written %c.' : esc_attr($instance['text']);
		$empty_string = !isset($instance['empty_string']) ? null : esc_attr($instance['empty_string']);

		/*
		procedure if user is not logged in
		*/

		if(!is_user_logged_in()) {

			/*
			user doesn't want to display widget
			*/

			if (empty($empty_string))
				return;

			/*
			user want to display empty string
			*/

			else
				$count_string=$empty_string;
		}

		/*
		user logged in
		*/

		else {

			/*
			retrieve formatted comment-count
			for currently logged in user
			*/

			$params=array(
				'user_attribute' => null,
				'format' => true,
				'display' => false
			);

			$formatted_comment_count=$featuring_countcomments->count_by_user($params);

			/*
			replace %c in widget-option
			text and output the result
			*/

			$count_string=str_replace('%c', $formatted_comment_count, $text);
		}

		echo $before_widget;
		echo $before_title . $title . $after_title;
		echo '<ul><li>'.$count_string.'</li></ul>';
	    	echo $after_widget;
	}

	/*
	the backend-form
	*/

	function form($instance) {
		global $featuring_countcomments;

		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$text = isset($instance['text']) ? esc_attr($instance['text']) : 'You\'ve already written %c.';
		$empty_string = isset($instance['empty_string']) ? esc_attr($instance['empty_string']) : '';
		?>

		<p><label for="<?php echo $this->get_field_id('title'); ?>">
		<?php _e('Title:'); ?>

		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>

		<p><label for="<?php echo $this->get_field_id('text'); ?>">
		<?php _e('Text: (%c gets replaced with formatted comment-count)'); ?>

		<input class="widefat" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>" type="text" value="<?php echo $text; ?>" /></label></p>

		<p><label for="<?php echo $this->get_field_id('empty_string'); ?>">
		<?php _e('Empty String: (leave empty to suppress display of widget if no user is logged in)'); ?>

		<input class="widefat" id="<?php echo $this->get_field_id('empty_string'); ?>" name="<?php echo $this->get_field_name('empty_string'); ?>" type="text" value="<?php echo $empty_string; ?>" /></label></p>

		<p><a href='options-general.php?page=<?php echo($featuring_countcomments->get_prefix(false)); ?>'><?php _e('Settings'); ?></a></p>

		<?php
	}

	/*
	saves updated widget-options
	*/

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['text'] = strip_tags($new_instance['text']);
		$instance['empty_string'] = strip_tags($new_instance['empty_string']);

		if (empty($instance['text']))
			$instance['text'] = 'You\'ve already written %c.';

		return $instance;
	}

}

/*
UNINSTALL
*/

function featuring_countcomments_uninstall() {
 
		/*
		security check
		*/

		if (!current_user_can('manage_options'))
			wp_die(__('You do not have sufficient permissions to manage options for this blog.'));

		/*
		delete option-array
		*/

		delete_option('featuring_countcomments');

		/*
		delete widget-options
		*/

		delete_option('widget_featuring_countcomments');
	}

register_uninstall_hook(__FILE__, 'featuring_countcomments_uninstall');

/*
DEPRECATED FUNCTIONS
*/

function fcc_get_comment_count($comment) {
	global $featuring_countcomments;

	$featuring_countcomments->deprecated_function(__FUNCTION__, '1.00', 'count_by_comment');

	$params=array(
		'comment' => $comment,
		'format' => false,
		'display' => false,
		'in_loop' => true
	);

	return $featuring_countcomments->count_by_comment($params);
}

function fcc_comment_count($zero='0 comments', $one='1 comment', $more='%c comments') {
	global $featuring_countcomments;

	$featuring_countcomments->deprecated_function(__FUNCTION__, '1.00', 'count_by_comment');

	$params=array(
		'format' => true,
		'zero' => $zero,
		'one' => $one,
		'more' => $more,
		'display' => true,
		'in_loop' => true
	);

	$featuring_countcomments->count_by_comment($params);
}

function fcc_get_count_comments_author($author) {
	global $featuring_countcomments;

	$featuring_countcomments->deprecated_function(__FUNCTION__, '1.00', 'count_by_user');

	$params=array(
		'user_attribute' => $author,
		'query_type' => 'display_name',
		'format' => false,
		'display' => false
	);

	return $featuring_countcomments->count_by_user($params);
}

function fcc_get_count_comments_authorID($authorID) {
	global $featuring_countcomments;

	$featuring_countcomments->deprecated_function(__FUNCTION__, '1.00', 'count_by_user');

	$params=array(
		'user_attribute' => $authorID,
		'query_type' => 'user_id',
		'format' => false,
		'display' => false
	);

	return $featuring_countcomments->count_by_user($params);
}

function fcc_count_comments_author($zero='0 comments', $one='1 comment', $more='%c comments') {
	global $featuring_countcomments;

	$featuring_countcomments->deprecated_function(__FUNCTION__, '1.00', 'count_by_user');

	$params=array(
		'query_type' => 'user_id',
		'format' => true,
		'display' => true
	);

	$featuring_countcomments->count_by_user($params);
}

function fcc_count_comments_by_author($zero='0 comments', $one='1 comment', $more='%c comments', $author) {
	global $featuring_countcomments;

	$featuring_countcomments->deprecated_function(__FUNCTION__, '1.00', 'count_by_user');

	$params=array(
		'user_attribute' => $author,
		'query_type' => 'display_name',
		'format' => true,
		'display' => true
	);

	$featuring_countcomments->count_by_user($params);
}

function fcc_count_comments_by_authorID($zero='0 comments', $one='1 comment', $more='%c comments', $authorID) {
	global $featuring_countcomments;

	$featuring_countcomments->deprecated_function(__FUNCTION__, '1.00', 'count_by_user');

	$params=array(
		'user_attribute' => $authorID,
		'query_type' => 'user_id',
		'format' => true,
		'display' => true
	);

	$featuring_countcomments->count_by_user($params);
}

?>