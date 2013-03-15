<?php
/*
  Plugin Name: What The File
  Plugin URI: http://www.barrykooij.com/what-the-file/
  Description: What The File adds an option to your toolbar showing you what file is used to display the page you’re on. If you want to you can click the file name to edit it directly through the theme editor. Supports BuddyPress and Roots Theme. More information can be found at the <a href='http://wordpress.org/extend/plugins/what-the-file/'>WordPress plugin page</a>.
  Version: 1.2.0
  Author: Barry Kooij
  Author URI: http://www.barrykooij.com/
	License: GPL v3

	What The File Plugin
	Copyright (C) 2012-2013, Barry Kooij - barry@cageworks.nl

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class WhatTheFile
{
	const OPTION_INSTALL_DATE 			= 'whatthefile-install-date';
	const OPTION_ADMIN_NOTICE_KEY 	= 'whatthefile-hide-notice';
  private $template_name  				= '';

	public static function plugin_activation()
	{
		self::insert_install_date();
	}

  public function __construct()
  {
		$this->hooks();
  }

	private static function insert_install_date()
	{
		$datetime_now = new DateTime();
		$date_string 	= $datetime_now->format('Y-m-d');
		add_site_option( self::OPTION_INSTALL_DATE, $date_string, '', 'no' );
		return $date_string;
	}

	private function get_install_date()
	{
		$date_string = get_site_option( self::OPTION_INSTALL_DATE,  '' );
		if( $date_string == '' ) {
			// There is no install date, plugin was installed before version 1.2.0. Add it now.
			$date_string = self::insert_install_date();
		}
		return new DateTime( $date_string );
	}

	private function check_admin()
	{
		if( !is_super_admin() || !is_admin_bar_showing() ){ return false; }
		if( is_admin() ){ return false; }
		return true;
	}

	private function get_current_page()
	{
		return $this->template_name;
	}

	private function get_admin_querystring_array()
	{
		parse_str( $_SERVER['QUERY_STRING'], $params );
		return $params;
	}

	private function hooks()
	{
		// Admin notice hide catch
		add_action( 'admin_init', array( $this, 'catch_hide_notice' ) );

		// Is admin notice hidden?
		global $current_user;
		$hide_notice = get_user_meta( $current_user->ID, self::OPTION_ADMIN_NOTICE_KEY, true );

		if( current_user_can( 'install_plugins' ) && $hide_notice == '' ) {
			// Get installation date
			$datetime_install = $this->get_install_date();
			$datetime_past = new DateTime( '-10 days' );

			if( $datetime_past >= $datetime_install ) {
				// 10 or more days ago, show admin notice
				add_action( 'admin_notices', array( $this, 'display_admin_notice' ) );
			}
		}

		// Check if is frontend and user is admin
		if( !$this->check_admin() ) {
			return false;
		}

		// WTF actions and filers
		add_action( 'wp_head'						, array( $this, 'print_css' ) );
		add_filter( 'template_include'	, array( $this, 'save_current_page' ), 1000 );
		add_action( 'admin_bar_menu'		, array( $this, 'admin_bar_menu' ), 1000 );

		// BuddyPress hook
		if( class_exists( 'BuddyPress' ) ) {
			add_action( 'bp_core_pre_load_template', array( $this, 'save_buddy_press_template' ) );
		}
	}

	public function catch_hide_notice()
	{
		if( isset( $_GET[ self::OPTION_ADMIN_NOTICE_KEY ] ) && current_user_can( 'install_plugins' ) ) {
			// Add user meta
			global $current_user;
			add_user_meta( $current_user->ID, self::OPTION_ADMIN_NOTICE_KEY, '1', true );

			// Build redirect URL
			$query_params = $this->get_admin_querystring_array();
			unset( $query_params[ self::OPTION_ADMIN_NOTICE_KEY ] );
			$query_string = http_build_query( $query_params );
			if( $query_string != '' ) {
				$query_string = '?' . $query_string;
			}

			$redirect_url = 'http';
			if( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] == 'on' ) {
				$redirect_url .= 's';
			}
			$redirect_url .= '://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'PHP_SELF' ] . $query_string;

			// Redirect
			wp_redirect( $redirect_url );
			exit;
		}
	}

	public function display_admin_notice()
	{
		$query_params = $this->get_admin_querystring_array();
		$query_string = '?' . http_build_query( array_merge( $query_params, array( self::OPTION_ADMIN_NOTICE_KEY => '1' ) ) );

		echo '<div class="updated"><p>';
    printf(__("You've been using <b>What The File</b> for some time now, could you please give it a review at wordpress.org? <br /><br /> <a href='%s' target='_blank'>Yes, take me there!</a> - <a href='%s'>I've already done this!</a>"), 'http://wordpress.org/support/view/plugin-reviews/what-the-file', $query_string );
   	echo "</p></div>";
	}

	public function save_buddy_press_template( $template )
	{
		if( $this->template_name == '' ) {
			$this->template_name = str_ireplace( get_template_directory() . '/', '', $template );
		}
	}
  
  public function save_current_page( $template_name )
  {
		$this->template_name = basename( $template_name );

		// Do Roots Theme check
		if( function_exists( 'roots_template_path' ) ) {
			$this->template_name = basename( roots_template_path() );
		}

    return $template_name;
  }

  public function admin_bar_menu() {
    global $wp_admin_bar;      
    $wp_admin_bar->add_menu( array( 'id' => 'wtf-bar'			, 'parent' => 'top-secondary'	, 'title' => __( 'What The File', 'what-the-file' )	, 'href' => FALSE ) );
    $wp_admin_bar->add_menu( array( 'id' => 'wtf-bar-sub'	, 'parent' => 'wtf-bar'				, 'title' => $this->get_current_page()							, 'href' => get_admin_url() . 'theme-editor.php?file=' . $this->get_current_page() . '&theme=' . get_template() ) );
  }
  
  public function print_css()
  {
    echo "<style type=\"text/css\" media=\"screen\">#wp-admin-bar-wtf-bar #wp-admin-bar-wtf-bar-sub .ab-item{display: block !important;text-align: right;}</style>\n";
  }
  
}

add_action( 'plugins_loaded', create_function( '', 'new WhatTheFile();' ) );
register_activation_hook( __FILE__, array( 'WhatTheFile', 'plugin_activation' ) );