<?php

/*
Plugin Name: Stats for S2Member
Plugin URI: http://HelpForWP.com
Description: Statistics of what member you have and when they have signed up
Version: 1.0.1
Author: HelpForWP
Author URI: http://HelpForWP.com

------------------------------------------------------------------------

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, 
or any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/




class Stats4S2Member {

	public function __construct() {
		
		add_action('init', array(&$this, 'stats_4_s2member_register_styles'));
		add_action('wp_print_styles', array(&$this, 'stats_4_s2member_enqueue_styles'));
		
		
		add_action('init', array(&$this, 'stats_4_s2member_register_scripts'));
		add_action('wp_print_scripts', array(&$this, 'stats_4_s2member_enqueue_scripts'));
		
		register_activation_hook( __FILE__, array( &$this, 'stats_4_s2member_activate' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'stats_4_s2member_deactivate' ) );
		register_uninstall_hook( __FILE__, 'Stats4S2Member::stats_4_s2member_uninstall' );
	}

	function stats_4_s2member_register_styles() {
		//wp_register_style('stockticker_style', plugins_url('css/wp-stock-ticker.css', __FILE__));
	}
	
	function stats_4_s2member_enqueue_styles() {
		//wp_enqueue_style( 'stockticker_style');
	}
  
	function stats_4_s2member_register_scripts() {
		//wp_register_script('li-scroller', plugins_url('js/jquery.li-scroller.1.0.js', __FILE__));
	}
  
	function stats_4_s2member_enqueue_scripts() {
		//wp_enqueue_script( 'wp-stock-ticker', plugin_dir_url( __FILE__ ) . 'js/wp-stock-ticker.js', array( 'jquery' ) );
		//wp_localize_script( 'wp-stock-ticker', 'stats_4_s2member', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'stNonce' => wp_create_nonce( 'stock-ticker-nonce' ),) ); 
		//wp_enqueue_script( 'li-scroller' );
	}



	function stats_4_s2member_activate() {
		//once s2member actived $GLOBALS["WS_PLUGIN__"]["s2member"] will be set
		if ( !is_plugin_active('s2member/s2member.php') && !isset($GLOBALS["WS_PLUGIN__"]["s2member"]) ) {
            die("This Plugin requires s2Member® installed and activated first!");
		}
		
	}
	
	
	function stats_4_s2member_deactivate(){
		
	}


	public static function stats_4_s2member_uninstall() {
		
	}
	
	function add_admin_menus(){
		$s4s2_s2memberParentMenuSlug = 'ws-plugin--s2member-start';
		add_submenu_page($s4s2_s2memberParentMenuSlug, "", '<span style="display:block; margin:1px 0 1px -5px; padding:0; height:1px; line-height:1px; background:#CCCCCC;"></span>', "create_users", "#");
		add_submenu_page($s4s2_s2memberParentMenuSlug, "Stats for S2member", "Stats for S2Member", "create_users", 'stats_4_s2member', array(&$this, 'stats_4_s2member_submenu_page_callback') ); 
	}
	
	function stats_4_s2member_submenu_page_callback() {
		if ( ! current_user_can( 'list_users' ) ){
			wp_die( __( 'Cheatin&#8217; uh?' ) );
		}		
		require_once( 'inc/s4s2MemberUsersList.php' );

		//for plugin tilte
		$s4s2_formStr = '';
		$s4s2_formStr .= '<div class="wrap">'."\n";
		$s4s2_formStr .= '<div class="h4wp-heading" style="float:left;">'."\n";
		$s4s2_formStr .= '<img src="'.plugins_url('images/help-for-wordpress-small.png', __FILE__).'" alt="Help For WordPress Logo"  style="float:left;"/>'."\n";
		$s4s2_formStr .= '<h1 style="float:left; padding:0 0 0 10px; font-size:26px; font-weight: normal;">Stats for S2Member</h1>'."\n";
		$s4s2_formStr .= '</div>'."\n";
		$s4s2_formStr .= '<div style="clear:both;"></div>'."\n";
		$s4s2_formStr .= '</div>'."\n";
		echo $s4s2_formStr;


		//for new registration
		$s4s2_formStr = '';
		$s4s2_formStr .= '<div class="wrap">';
		$s4s2_formStr .= '<h2>New registration statistics</h2>'."\n";
		$s4s2_formStr .= '<p>These are the new signups to your S2member site</p>'."\n";
		$s4s2_formStr .= $this->stats_4_s2member_get_stats( 'NEW', 'TODAY' );
		$s4s2_formStr .= $this->stats_4_s2member_get_stats( 'NEW', 'THISWEEK' );
		$s4s2_formStr .= $this->stats_4_s2member_get_stats( 'NEW', 'THISMONTH' );
		$s4s2_formStr .= '</div>';
		
		echo $s4s2_formStr;
				
		if ( isset( $_REQUEST['registeration'] ) ){
			$s4s2_usersList = new S4S2MemberUsersList();
			
			add_action("pre_user_query", array(&$s4s2_usersList, 'user_registration_query') );
			
			$s4s2_usersList->set_users_per_page( 10 );
			$s4s2_usersList->prepare_items();
			echo '<div class="wrap">';
			$s4s2_usersList->display();
			echo '</div>';
		}
		
		//for expiring registration
		$s4s2_formStr = '';
		$s4s2_formStr .= '<div class="wrap">';
		$s4s2_formStr .= '<h2>Expiring registration statistics</h2>'."\n";
		$s4s2_formStr .= '<p>These are the S2member registrations that are coming up to expire</p>'."\n";
		$s4s2_formStr .= $this->stats_4_s2member_get_stats( 'EXPIRING', 'TODAY' );
		$s4s2_formStr .= $this->stats_4_s2member_get_stats( 'EXPIRING', 'THISWEEK' );
		$s4s2_formStr .= $this->stats_4_s2member_get_stats( 'EXPIRING', 'THISMONTH' );
		$s4s2_formStr .= '</div>';
		echo $s4s2_formStr;
		
		if ( isset( $_REQUEST['expiredstart'] ) && isset( $_REQUEST['expiredend'] ) ){
			$s4s2_expiringUsersList = new S4S2MemberUsersList();
			
			add_action("pre_user_query", array(&$s4s2_expiringUsersList, 'user_registration_query') );
			
			$s4s2_expiringUsersList->set_users_per_page( 10 );
			$s4s2_expiringUsersList->prepare_items();
			echo '<div class="wrap">';
			$s4s2_expiringUsersList->display();
			echo '</div>';
		}
		
		//for footer
		require_once('inc/footer.php');
	}
	
	public function stats_4_s2member_get_stats( $type, $howDays, $is4DashobardMetabox = false ){
		global $wpdb;
		
		$s4s2_Start = 0;
		$s4s2_End = 0;
		$s4s2_gmt_offset_timestamp = get_option('gmt_offset') * 3600;

		switch ($howDays){
			case 'TODAY':
			default:
				$s4s2_Start = strtotime(date("Y-m-d 0:0:0"));
				$s4s2_End = strtotime(date("Y-m-d 23:59:59"));
				$s4s2_daysTitle = "Today";
				break;
			case 'THISWEEK':
				//in database is UTC time, but check which week need use local time
				
				if (date('N', time() + $s4s2_gmt_offset_timestamp) == 1){ //today is Monday
					$s4s2_thisWeekStartTime = date("Y-m-d 00:00:00", time());
				}else{
					$s4s2_thisWeekStartTime = date("Y-m-d 00:00:00", strtotime('next Monday') - 7*24*3600);
				}
				if (date('N', time() + $s4s2_gmt_offset_timestamp) == 7){ //today is Sunday
					$s4s2_thisWeekEndTime = date("Y-m-d 23:59:59", time());
				}else{
					$s4s2_thisWeekEndTime = date("Y-m-d 23:59:59", strtotime('next Sunday'));
				}
	
				$s4s2_Start = strtotime($s4s2_thisWeekStartTime);
				$s4s2_End = strtotime($s4s2_thisWeekEndTime);
				$s4s2_daysTitle = "This week";
				
				break;
			case 'THISMONTH':
				$s4s2_Start = mktime(0, 0, 0, date("n"), 1, date("Y") );
				$s4s2_End = mktime(23, 59, 59, date("n"), date("t"), date("Y") );
				$s4s2_daysTitle = "This month";
				
				break;
		}
		if ($type == 'EXPIRING'){
			$s4s2_query_from = " FROM `" . $wpdb->users . "` INNER JOIN `" . $wpdb->usermeta . "` ON `" . $wpdb->users . "`.`ID` = `" . $wpdb->usermeta . "`.`user_id`";
			$s4s2_query_where .= " WHERE `" . $wpdb->usermeta . "`.`meta_key` = '" . $wpdb->prefix . "s2member_auto_eot_time' ".
								 " AND `" . $wpdb->usermeta . "`.`meta_value` <= ".$s4s2_End . 
								 " AND `" . $wpdb->usermeta . "`.`meta_value` >= ".$s4s2_Start;
	
			$s4s2_count = $wpdb->get_var( "SELECT count( * ) ".$s4s2_query_from.$s4s2_query_where );
		}else{
			$s4s2_count = $wpdb->get_var( "SELECT count( * ) FROM `".$wpdb->users."` WHERE UNIX_TIMESTAMP( `user_registered` ) >= ".$s4s2_Start );
		}
		
		if (!$is4DashobardMetabox){
			$s4s2_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}else{
			$s4s2_url_array = explode('/', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			unset($s4s2_url_array[count($s4s2_url_array) -1]);
			$s4s2_url = implode('/', $s4s2_url_array);
			$s4s2_url = ( is_ssl() ? 'https://' : 'http://' ) . $s4s2_url.'/admin.php';
			
			$s4s2_url = add_query_arg( array( 'page' => 'stats_4_s2member'), $s4s2_url );
		}
		$s4s2_url = remove_query_arg( array('paged', 'registeration', 'expiredstart', 'expiredend'), $s4s2_url );
		
		if ($type == 'EXPIRING'){
			$s4s2_url = add_query_arg( array( 'expiredstart' => $s4s2_Start, 'expiredend' => $s4s2_End ), $s4s2_url );
		}else{
			$s4s2_url = add_query_arg( array( 'registeration' => $s4s2_Start ), $s4s2_url );
		}
		
		$s4s2_formStr .= '<p><span style="display:block; width:200px;float:left;">'.$s4s2_daysTitle.': '.$s4s2_count.'</span>';
		$s4s2_formStr .= $s4s2_count > 0 ? '<a href="'.$s4s2_url.'">view</a></p>' : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>';
		$s4s2_formStr .= "\n";
		
		return $s4s2_formStr;
	}
	
	function dashboard_stats4s2member_meta_content() {
		$s4s2_formStr = '';
		$s4s2_formStr .= '<p>These are the new signups to your S2members site</p>'."\n";
		$s4s2_formStr .= $this->stats_4_s2member_get_stats( 'NEW', 'TODAY', true );
		$s4s2_formStr .= $this->stats_4_s2member_get_stats( 'NEW', 'THISWEEK', true );
		$s4s2_formStr .= $this->stats_4_s2member_get_stats( 'NEW', 'THISMONTH', true );

		$s4s2_formStr .= '<p>These are the S2members registration that are coming up to expire</p>'."\n";
		$s4s2_formStr .= $this->stats_4_s2member_get_stats( 'EXPIRING', 'TODAY', true );
		$s4s2_formStr .= $this->stats_4_s2member_get_stats( 'EXPIRING', 'THISWEEK', true );
		$s4s2_formStr .= $this->stats_4_s2member_get_stats( 'EXPIRING', 'THISMONTH', true );
		
		echo $s4s2_formStr;
		
	}
	function dashboard_add_metabox() {
		
		add_meta_box( 'dashboard_widget_stats4s2member_id', 'S2Member Stats', array(&$this, 'dashboard_stats4s2member_meta_content'), 'dashboard', 'normal', 'high' );
	}
}

	
$Stats4S2Member = new Stats4S2Member();
//hooks
add_action("admin_menu", array( &$Stats4S2Member, 'add_admin_menus' ) );
add_action('wp_dashboard_setup', array( &$Stats4S2Member, 'dashboard_add_metabox' ) );

// this function is to accept remove username / password authentication when dealing with S2downloads

function test_authourization_fun( $vars ){

    if (isset($_GET['user']) && $_GET['user']){
        $_SERVER["PHP_AUTH_USER"] = $_GET['user'];
    }else{
        $_SERVER["PHP_AUTH_USER"] = 'NOUSER';
    }
    if (isset($_GET['pw']) && $_GET['pw']){
        $_SERVER["PHP_AUTH_PW"] = $_GET['pw'];
    }else{
        $_SERVER["PHP_AUTH_PW"] = 'ERRORPASSWORD';
    }
}

add_action('ws_plugin__s2member_during_check_file_remote_authorization_before', 'test_authourization_fun');

