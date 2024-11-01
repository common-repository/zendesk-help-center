<?php
/*
Plugin Name: Help Center by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/zendesk-help-center/
Description: Backup and export Zendesk Help Center content automatically to your WordPress website database.
Author: BestWebSoft
Text Domain: zendesk-help-center
Domain Path: /languages
Version: 1.1.2
Author URI: https://bestwebsoft.com/
License: GPLv3 or later
*/

/*  © Copyright 2021  BestWebSoft  ( https://support.bestwebsoft.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Function are using to add on admin-panel Wordpress page 'bws_panel' and sub-page of this plugin */
if ( ! function_exists( 'add_zndskhc_admin_menu' ) ) {
	function add_zndskhc_admin_menu() {
		global $submenu, $zndskhc_plugin_info, $wp_version;

		$settings = add_menu_page( 'Help Center ' . __( 'Settings', 'zendesk-help-center' ), 'Help Center', 'manage_options', 'zendesk_hc.php', 'zndskhc_settings_page', 'none' );

		add_submenu_page( 'zendesk_hc.php', 'Help Center  ' . __( 'Settings', 'zendesk-help-center' ), __( 'Settings', 'zendesk-help-center' ), 'manage_options', 'zendesk_hc.php', 'zndskhc_settings_page' );

		$backup_page = add_submenu_page( 'zendesk_hc.php', 'Help Center ', 'Help Center', 'manage_options', 'zendesk_hc_backup.php', 'zndskhc_backup_page' );

		$articles_page = add_submenu_page( 'zendesk_hc.php', 'Help Center ', __( 'Articles', 'zendesk-help-center' ), 'manage_options', 'zendesk_articles.php', 'zndskhc_articles_page' );

		$widget_statistic_page = add_submenu_page( 'zendesk_hc.php', 'Help Center ', __( 'Widget Statistic', 'zendesk-help-center' ), 'manage_options', 'zendesk_widget_statistic.php', 'zndskhc_widget_statistic_page' );

		add_submenu_page( 'zendesk_hc.php', 'BWS Panel', 'BWS Panel', 'manage_options', 'zndskhc-bws-panel', 'bws_add_menu_render' );

		if ( isset( $submenu['zendesk_hc.php'] ) ) {
			$submenu['zendesk_hc.php'][] = array(
				'<span style="color:#d86463"> ' . __( 'Upgrade to Pro', 'zendesk-help-center' ) . '</span>',
				'manage_options',
				'https://bestwebsoft.com/products/wordpress/plugins/zendesk-help-center/?k=036b375477a35a960f966d052591e9ed&amp;pn=208&amp;v=' . $zndskhc_plugin_info["Version"] . '&wp_v=' . $wp_version );
		}

		add_action( 'load-' . $backup_page, 'zndskhc_add_tabs' );
		add_action( 'load-' . $settings, 'zndskhc_add_tabs' );
	}
}

/* Function adds translations in this plugin */
if ( ! function_exists( 'zndskhc_plugins_loaded' ) ) {
	function zndskhc_plugins_loaded() {
		load_plugin_textdomain( 'zendesk-help-center', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

if ( ! function_exists ( 'zndskhc_init' ) ) {
	function zndskhc_init() {
		global $zndskhc_plugin_info;

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );

		if ( empty( $zndskhc_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$zndskhc_plugin_info = get_plugin_data( __FILE__ );
		}

		/* Function check if plugin is compatible with current WP version  */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $zndskhc_plugin_info, '4.5' );
	}
}

if ( ! function_exists( 'zndskhc_admin_init' ) ) {
	function zndskhc_admin_init() {
		global $pagenow, $bws_plugin_info, $zndskhc_plugin_info, $zndskhc_options;

		if ( empty( $bws_plugin_info ) ) {
			$bws_plugin_info = array( 'id' => '208', 'version' => $zndskhc_plugin_info["Version"] );
		}

		/* Call register settings function */
		if ( isset( $_GET['page'] ) && ( "zendesk_hc.php" == $_GET['page'] || "zendesk_hc_backup.php" == $_GET['page'] ) ) {
			register_zndskhc_settings();

			if ( isset( $_POST['zndskhc_export'] ) ) {
				zndskhc_export();
			} elseif ( isset( $_POST['zndskhc_synch'] ) ) {
				zndskhc_synchronize();
			}
		}

		if ( 'plugins.php' == $pagenow ) {
			if ( function_exists( 'bws_plugin_banner_go_pro' ) ) {
				if ( empty( $zndskhc_options ) )
					register_zndskhc_settings();
			
				bws_plugin_banner_go_pro( $zndskhc_options, $zndskhc_plugin_info, 'zndskhc', 'zendesk-help-center', '617141936fb69ce9c91a2160da415f24', '208', 'zendesk-help-center' );
			}
		}
	}
}

/* Function create column in table wp_options for option of this plugin. If this column exists - save value in variable. */
if ( ! function_exists( 'register_zndskhc_settings' ) ) {
	function register_zndskhc_settings() {
		global $zndskhc_options, $zndskhc_plugin_info;
		$plugin_db_version = '1.3';

		/* Install the option defaults */
		if ( ! get_option( 'zndskhc_options' ) ) {
			$options_defaults = zndskhc_get_options_default();
			add_option( 'zndskhc_options', $options_defaults );
		}
		$zndskhc_options = get_option( 'zndskhc_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $zndskhc_options['plugin_option_version'] ) || $zndskhc_options['plugin_option_version'] != $zndskhc_plugin_info["Version"] ) {
			
			$options_defaults = zndskhc_get_options_default();

			if ( '0' != $zndskhc_options['time'] ) {
				$time = time() + $zndskhc_options['time']*60*60;
				wp_schedule_event( $time, 'schedules_hours', 'auto_synchronize_zendesk_hc' );
			}

			$zndskhc_options = array_merge( $options_defaults, $zndskhc_options );
			$zndskhc_options['plugin_option_version'] = $zndskhc_plugin_info["Version"];
			$update_option = true;
		}

		if ( ! isset( $zndskhc_options['plugin_db_version'] ) || $zndskhc_options['plugin_db_version'] != $plugin_db_version ) {
			zndskhc_db_table();

			$zndskhc_options['plugin_db_version'] = $plugin_db_version;
			$update_option = true;
		}

		if ( isset( $update_option ) ) {
			update_option( 'zndskhc_options', $zndskhc_options );
		}
	}
}

if ( ! function_exists( 'zndskhc_get_options_default' ) ) {
	function zndskhc_get_options_default() {
		global $zndskhc_plugin_info;

		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}
		$email = 'wordpress@' . $sitename;

		$options_defaults = array(
			'plugin_option_version'		=> $zndskhc_plugin_info["Version"],
			'plugin_db_version'			=> '',
			'display_settings_notice'	=> 0,
			'suggest_feature_banner'	=> 1,			

			'subdomain'					=> '',
			'user'						=> '',
			'password'					=> '',
			'token'						=> '',
			'time'						=> '48',
			'backup_elements'			=> array(
				'categories'	=> '1',
				'sections'		=> '1',
				'articles'		=> '1',
				'comments'		=> '1',
				'labels'		=> '1',
				'attachments'	=> '1'
			),
			'emailing_fail_backup'		=> '1',
			'email'						=> $email,
			'last_synch'				=> ''
		);

		return $options_defaults;
	}
}

if ( ! function_exists( 'zndskhc_db_table' ) ) {
	function zndskhc_db_table() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE `" . $wpdb->prefix . "zndskhc_categories` (
			`id` bigint UNSIGNED NOT NULL,
			`position` int NOT NULL,
			`updated_at` datetime NOT NULL,
			`name` char(255) NOT NULL,
			`description` char(255) NOT NULL,
			`locale` char(5) NOT NULL,
			`source_locale` char(5) NOT NULL,
			UNIQUE KEY id (id)
		);";
		dbDelta( $sql );

		$sql = "CREATE TABLE `" . $wpdb->prefix . "zndskhc_sections` (
			`id` bigint UNSIGNED NOT NULL,
			`category_id` bigint UNSIGNED NOT NULL,
			`position` int NOT NULL,
			`updated_at` datetime NOT NULL,
			`name` char(255) NOT NULL,
			`description` char(255) NOT NULL,
			`locale` char(5) NOT NULL,
			`source_locale` char(5) NOT NULL,
			UNIQUE KEY id (id)
		);";
		dbDelta( $sql );

		$sql = "CREATE TABLE `" . $wpdb->prefix . "zndskhc_articles` (
			`id` bigint UNSIGNED NOT NULL,
			`category_id` bigint UNSIGNED NOT NULL,
			`section_id` bigint UNSIGNED NOT NULL,
			`position` int NOT NULL,
			`author_id` bigint UNSIGNED NOT NULL,
			`comments_disabled` int(1) NOT NULL,
			`promoted` int(1) NOT NULL,
			`updated_at` datetime NOT NULL,
			`name` char(255) NOT NULL,
			`title` char(255) NOT NULL,
			`body` text NOT NULL,
			`locale` char(5) NOT NULL,
			`source_locale` char(5) NOT NULL,
			`labels` char(255),
			UNIQUE KEY id (id)
		);";
		dbDelta( $sql );

		$sql = "CREATE TABLE `" . $wpdb->prefix . "zndskhc_labels` (
			`id` bigint UNSIGNED NOT NULL,
			`name` char(255) NOT NULL,
			`updated_at` datetime NOT NULL,
			UNIQUE KEY id (id)
		);";
		dbDelta( $sql );

		$sql = "CREATE TABLE `" . $wpdb->prefix . "zndskhc_comments` (
			`id` bigint UNSIGNED NOT NULL,
			`author_id` bigint UNSIGNED NOT NULL,
			`source_type` char(255) NOT NULL,
			`source_id` bigint UNSIGNED NOT NULL,
			`body` text NOT NULL,
			`locale` char(5) NOT NULL,
			`updated_at` datetime NOT NULL,
			UNIQUE KEY id (id)
		);";
		dbDelta( $sql );

		$sql = "CREATE TABLE `" . $wpdb->prefix . "zndskhc_attachments` (
			`id` bigint UNSIGNED NOT NULL,
			`url` char(255) NOT NULL,
			`article_id` bigint UNSIGNED NOT NULL,
			`file_name` char(255) NOT NULL,
			`content_url` char(255) NOT NULL,
			`content_type` char(255) NOT NULL,
			`size` int NOT NULL,
			`inline` TINYINT(1) NOT NULL,
			`created_at` datetime NOT NULL,
			`updated_at` datetime NOT NULL,
			UNIQUE KEY id (id)
		);";
		dbDelta( $sql );
	}
}

if ( ! function_exists( 'zndskhc_activation_hook' ) ) {
	function zndskhc_activation_hook() {
		global $zndskhc_options;
		register_zndskhc_settings();
		zndskhc_db_table();
	}
}

/* Function is forming page of the settings of this plugin */
if ( ! function_exists( 'zndskhc_backup_page' ) ) {
	function zndskhc_backup_page() {
		global $wpdb, $wp_version, $zndskhc_options, $zndskhc_plugin_info, $zndskhc_options_default, $zndskhc_error, $zndskhc_zip_exist;
		$message = $error = '';
		$plugin_basename = plugin_basename( __FILE__ );

		$elements = array(
			'categories' 	=> __( 'Categories' , 'zendesk-help-center' ),
			'sections' 		=> __( 'Sections' , 'zendesk-help-center' ),
			'articles' 		=> __( 'Articles' , 'zendesk-help-center' ),
			'comments' 		=> __( 'Articles Comments' , 'zendesk-help-center' ),
			'labels' 		=> __( 'Articles Labels' , 'zendesk-help-center' ),
			'attachments' 	=> __( 'Articles Attachments' , 'zendesk-help-center' )
		);

		$file_check_name = dirname( __FILE__ )  . "/backup.log";
		if ( ! file_exists( $file_check_name ) ) {
			if ( $handle = @fopen( $file_check_name, "w+" ) ) {
				fclose( $handle );
			} else {
				$log_error = __( "Error creating log file" , 'zendesk-help-center' ) . ' ' . $file_check_name . '.';
			}
		}
		
		if ( ! empty( $zndskhc_error ) ) {
			$error = $zndskhc_error;
		}

		if ( isset( $_REQUEST['zndskhc_synch'] ) && check_admin_referer( $plugin_basename, 'zndskhc_nonce_name_synch' ) ) {
			$result = zndskhc_synchronize( false );
			if ( true !== $result ) {
				$error = $result;
			} else {
				$message = __( "Data is successfully updated." , 'zendesk-help-center' );
			}
		} ?>
		<div class="wrap">
			<h1><?php _e( 'Help Center', 'zendesk-help-center' ); ?></h1>
			<div class="updated fade below-h2" <?php if ( '' == $message || '' != $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error below-h2" <?php if ( '' == $error ) echo 'style="display:none"'; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<?php if ( ! empty( $zndskhc_options['last_synch'] ) ) { ?>
				<p><?php _e( 'Last synchronization with Zendesk Help Center was on' , 'zendesk-help-center' ); echo ' ' . $zndskhc_options['last_synch']; ?></p>
			<?php }

			$current_exist_backup = array();

			foreach ( $elements as $key => $value ) {
				$count = $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->prefix . "zndskhc_" . $key . "`" );
				if ( ! empty( $count ) ) {
					$current_exist_backup[ $key ] = $count;
				}
			}

			if ( ! empty( $log_error ) ) { ?>
				<div class="error below-h2"><p><?php echo $log_error; ?></p></div>
			<?php } else {
				zndskhc_get_logs();
			} ?>
			<form method="post" action="">
				<p style="display: inline-block;">
					<input type="submit" class="button-primary zndskhc_submit_button" value="<?php _e( 'Synchronize now', 'zendesk-help-center' ); ?>" />
					<img class="zndskhc_loader" src="<?php echo plugins_url( 'images/ajax-loader.gif', __FILE__ ); ?>" />
					<?php wp_nonce_field( $plugin_basename, 'zndskhc_nonce_name_synch' ); ?>
					<input type="hidden" name="zndskhc_synch" value="submit" />
				</p>
			</form>
			<form method="post" action="">
				<?php if ( ! empty( $current_exist_backup ) ) { ?>
					<p style="display: inline-block;">
						<input type="submit" class="button-primary zndskhc_submit_button zndskhc_export" value="<?php _e( 'Export backup', 'zendesk-help-center' ); ?>" />
						<img class="zndskhc_loader" src="<?php echo plugins_url( 'images/ajax-loader.gif', __FILE__ ); ?>" />
						<?php wp_nonce_field( $plugin_basename, 'zndskhc_nonce_name_export' ); ?>
						<input type="hidden" name="zndskhc_export" value="submit" />
					</p>
				<?php } ?>
			</form>			
		</div>
	<?php }
}

if ( ! function_exists( 'zndskhc_settings_page' ) ) {
	function zndskhc_settings_page() {		
		if ( ! class_exists( 'Bws_Settings_Tabs' ) )
			require_once( dirname( __FILE__ ) . '/bws_menu/class-bws-settings.php' );
		require_once( dirname( __FILE__ ) . '/includes/class-zndskhc-settings.php' );
		$page = new Zndskhc_Settings_Tabs( plugin_basename( __FILE__ ) ); 
		if ( method_exists( $page, 'add_request_feature' ) )
            $page->add_request_feature(); ?>
		<div class="wrap">
			<h1><?php _e( 'Help Center Settings', 'zendesk-help-center' ); ?></h1>
			<?php $page->display_content(); ?>
		</div>
	<?php }
}

if ( ! function_exists( 'zndskhc_articles_page' ) ) {
	function zndskhc_articles_page() { ?>
		<div class="wrap">
			<p>
				<?php _e( 'This tab contains Pro options only.', 'zendesk-help-center' ); ?>
			</p>
		</div>
	<?php }
}

if ( ! function_exists( 'zndskhc_widget_statistic_page' ) ) {
	function zndskhc_widget_statistic_page() { ?>
		<div class="wrap">
			<p>
				<?php _e( 'This tab contains Pro options only.', 'zendesk-help-center' ); ?>
			</p>
		</div>
	<?php }
}

if ( ! function_exists( 'zndskhc_export' ) ) {
	function zndskhc_export() {
		global $wpdb, $zndskhc_error, $zndskhc_zip_exist, $zndskhc_options;

		register_zndskhc_settings();

		$zndskhc_zip_exist = class_exists( 'ZipArchive' );

		if ( isset( $_REQUEST['zndskhc_export'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'zndskhc_nonce_name_export' ) ) {

			if ( ! isset( $zndskhc_options['backup_elements'] ) ) {
				$zndskhc_error = __( 'Please, choose at least one element.', 'zendesk-help-center' );
			} else {
				$csv_filenames = $results = array();
				$elements = $zndskhc_options['backup_elements'];

				foreach ( $elements as $element => $val ) {
					$element = sanitize_text_field( $element );
					
					$results[ $element ] = $wpdb->get_results( "SELECT * FROM `" . $wpdb->prefix . 'zndskhc_' . $element . "`", ARRAY_A );

					if ( ! empty( $results[ $element ] ) ) {
						/* Write column names */
						$colArray = array_keys( $results[ $element ][0] );

						$filename = tempnam( sys_get_temp_dir(), 'csv' );
						$csv_filenames[ $element ] = $filename;
						$file = fopen( $filename, "w" );
						fputcsv( $file, $colArray, ';' );
						foreach ( $results[ $element ] as $result ) {
							fputcsv( $file, $result, ';' );
						}
						fclose( $file );
					}
				}

				if ( ! empty( $csv_filenames ) ) {
					if ( ! $zndskhc_zip_exist || 1 == ( count( $csv_filenames ) && 'attachments' != key( $csv_filenames ) ) ) {
						header( "Content-Type: application/csv" );
						header( "Content-Disposition: attachment;Filename=zendesk_hc_backup_" . key( $csv_filenames ) . ".csv" );
						/* Send file to browser */
						readfile( $filename );
						unlink( $filename );
					} else {
						/* create zip */
						$filename_zip = tempnam( sys_get_temp_dir(), 'zip' );
						$zip = new ZipArchive();

						if ( true === $zip->open( $filename_zip, ZIPARCHIVE::CREATE ) ) {
							foreach ( $csv_filenames as $key => $value ) {
								$zip->addFile( $value, $key . '.csv' );
							}

							if ( array_key_exists( 'attachments', $csv_filenames ) ) {
								$upload_dir = wp_upload_dir();
								$folder = $upload_dir['basedir'] . '/zendesk_hc_attachments';

								foreach ( $results['attachments'] as $key => $value ) {
									$uploadfile = $folder . '/' . $value['id'] . '-' . $value['file_name'];
									$zip->addFile( $uploadfile, 'attachments/' . $value['file_name'] );
								}
							}

							$zip->close();
						}

						if ( file_exists( $filename_zip ) && 0 < filesize( $filename_zip ) ) {
							header( "Content-Type: application/zip" );
							header( "Content-Disposition: attachment;Filename=zendesk_hc_backup.zip" );
							/* Send file to browser */
							readfile( $filename_zip );
							unlink( $filename_zip );
						} else {
							$zndskhc_error = __( 'Error creating zip archive.', 'zendesk-help-center' );
						}
					}
				}
				exit();
			}
		}
	}
}

if ( ! function_exists( 'zndskhc_remote_get' ) ) {
	function zndskhc_remote_get( $url, $attempt = 1 ) {
		global $zndskhc_options;

		if ( ! empty( $zndskhc_options['token'] ) ) {
			$userpwd = $zndskhc_options['user'] . '/token:' . $zndskhc_options['token'];
		} else {
			$userpwd = $zndskhc_options['user'] . ':' . $zndskhc_options['password'];
		}

		$args = array(
		    'headers' => array(
		        'Authorization' => 'Basic ' . base64_encode( $userpwd )
		    )
		);
		$raw_response = wp_remote_get( $url, $args );

		if ( is_wp_error( $raw_response ) ) {
			return array( 'error' => $raw_response->get_error_code(), 'description' => $raw_response->get_error_message() );		
		} else {
			$response = maybe_unserialize( wp_remote_retrieve_body( $raw_response ) );
			$response_code = wp_remote_retrieve_response_code( $raw_response );
			
			if ( 200 != $response_code ) {
				return array( 'error' => $response_code, 'description' => $response );	
			} else {

				$array_resp = json_decode( $response, true );				

				if ( !is_array( $array_resp ) && empty( $array_resp ) && ! empty( $attempt ) ) {
					/* too many remote get/ try again later */
					sleep(5);
					$array_resp = zndskhc_remote_get( $url, 2 );
				}
				return $array_resp;
			}
		}		
	}
}

if ( ! function_exists( 'zndskhc_synchronize' ) ) {
	function zndskhc_synchronize( $auto_mode = true ) {
		global $wpdb, $zndskhc_options;

		if ( empty( $zndskhc_options ) ) {
			$zndskhc_options = get_option( 'zndskhc_options' );
		}

		if ( empty( $zndskhc_options['subdomain'] ) || empty( $zndskhc_options['user'] ) || empty( $zndskhc_options['password'] ) ) {
			$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Backup failed as some plugin settings are empty. To fix it go to', 'zendesk-help-center' ) . ' <a href="' . get_admin_url( null, 'admin.php?page=zendesk_hc.php' ) . '">' . __( 'the settings page', 'zendesk-help-center' ) . '</a>.';
			zndskhc_log( $log );
			if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
				$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Backup failed as some plugin settings are empty. To fix it go to', 'zendesk-help-center' ) . ' <a href="' . get_admin_url( null, 'admin.php?page=zendesk_hc.php' ) . '">' . __( 'the settings page', 'zendesk-help-center' ) . '</a>.';
				zndskhc_send_mail( $log );
			}
			return $log;
		}

		/* get categories */
		if ( ! empty( $zndskhc_options['backup_elements']['categories'] ) ) {
			$all_categories = $wpdb->get_results( "SELECT `id`, `updated_at` FROM `" . $wpdb->prefix . "zndskhc_categories`", ARRAY_A );
			$added = $updated = $deleted = 0;
			$i = 1;
			while ( $i != false ) {

				$url = 'https://' . $zndskhc_options['subdomain'] . '.zendesk.com/api/v2/help_center/categories.json?page=' . $i . '&per_page=30';
				$array_resp = zndskhc_remote_get( $url );

				if ( !is_array( $array_resp ) && empty( $array_resp ) ) {
					$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Categories backup', 'zendesk-help-center' ) . ' - ' . __( 'Undefined error has occurred while getting data from Zendesk API.', 'zendesk-help-center' );
					zndskhc_log( $log );
					if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
						zndskhc_send_mail( $log );
					}
					return $log;
				}

				if ( isset( $array_resp['error'] ) ) {
					if ( 'RecordNotFound' == $array_resp['error'] ) {
						$log = __( 'WARNING', 'zendesk-help-center' ) . ': ' . __( 'Categories backup', 'zendesk-help-center' ) . ' - ' . $array_resp['error'] . ' (' . $array_resp['description'] . ')';
						zndskhc_log( $log );
						break;
					} else {
						$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Categories backup', 'zendesk-help-center' ) . ' - ' . $array_resp['error'] . ' (' . $array_resp['description'] . ')';
						zndskhc_log( $log );
						if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
							zndskhc_send_mail( $log );
						}
						return $log;
					}
				} else {

					foreach ( $array_resp['categories'] as $key => $value ) {
						$category = false;
						foreach ( $all_categories as $key_cat => $value_cat ) {
							if ( $value_cat['id'] == $value['id'] ) {
								$category = $value_cat;
								unset( $all_categories[ $key_cat ] );
								break;
							}
						}

						if ( empty( $category ) ) {
							$wpdb->insert( $wpdb->prefix . "zndskhc_categories",
								array( 'id' 			=> $value['id'],
										'position' 		=> $value['position'],
										'updated_at'	=> $value['updated_at'],
										'name'			=> $value['name'],
										'description'	=> $value['description'],
										'locale'		=> $value['locale'],
										'source_locale'	=> $value['source_locale'] ),
								array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
							$added++;
						} elseif ( strtotime( $category['updated_at'] ) < strtotime( $value['updated_at'] ) ) {
							$wpdb->update( $wpdb->prefix . "zndskhc_categories",
								array( 'position' 		=> $value['position'],
										'updated_at'	=> $value['updated_at'],
										'name'			=> $value['name'],
										'description'	=> $value['description'],
										'locale'		=> $value['locale'],
										'source_locale'	=> $value['source_locale'] ),
								array( 'id' => $value['id'] ),
								array(  '%s', '%s', '%s', '%s', '%s', '%s' ) );
							$updated++;
						}
					}

					if ( empty( $array_resp['next_page'] ) ) {
						break;
					}
				}
				$i++;
			}

			if ( ! empty( $all_categories ) ) {
				foreach ( $all_categories as $key_cat => $value_cat ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_categories` WHERE `id` = %s", $value_cat['id'] ) );
					$deleted++;
				}
			}

			if ( $added != 0 || $updated != 0 || $deleted != 0 ) {
				$log = __( 'Categories backup', 'zendesk-help-center' ) . ':';
				if ( $added != 0 ) {
					$log .= ' ' . $added . ' ' . __( 'added', 'zendesk-help-center' ) . ';';
				}
				if ( $updated != 0 ) {
					$log .= ' ' . $updated . ' ' . __( 'updated', 'zendesk-help-center' ) . ';';
				}
				if ( $deleted != 0 ) {
					$log .= ' ' . $deleted . ' ' . __( 'deleted', 'zendesk-help-center' ) . ';';
				}
				zndskhc_log( $log );
			}
		}

		/* get sections */
		if ( ! empty( $zndskhc_options['backup_elements']['sections'] ) ) {
			$all_sections = $wpdb->get_results( "SELECT `id`, `updated_at` FROM `" . $wpdb->prefix . "zndskhc_sections`", ARRAY_A );
			$added = $updated = $deleted = 0;
			$i = 1;
			while ( $i != false ) {
				$url = 'https://' . $zndskhc_options['subdomain'] . '.zendesk.com/api/v2/help_center/sections.json?page=' . $i . '&per_page=30';
				$array_resp = zndskhc_remote_get( $url );

				if ( !is_array( $array_resp ) && empty( $array_resp ) ) {
					$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Sections backup', 'zendesk-help-center' ) . ' - ' .  __( 'Undefined error has occurred while getting data from Zendesk API.', 'zendesk-help-center' );
					zndskhc_log( $log );
					if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
						zndskhc_send_mail( $log );
					}
					return $log;
				}

				if ( isset( $array_resp['error'] ) ) {
					if ( 'RecordNotFound' == $array_resp['error'] ) {
						$log = __( 'WARNING', 'zendesk-help-center' ) . ': ' . __( 'Sections backup', 'zendesk-help-center' ) . ' - ' . $array_resp['error'] . ' (' . $array_resp['description'] . ')';
						zndskhc_log( $log );
						break;
					} else {
						$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Sections backup', 'zendesk-help-center' ) . ' - ' . $array_resp['error'] . ' (' . $array_resp['description'] . ')';
						zndskhc_log( $log );
						if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
							zndskhc_send_mail( $log );
						}
						return $log;
					}
				} else {

					foreach ( $array_resp['sections'] as $key => $value ) {
						$section = false;
						foreach ( $all_sections as $key_sec => $value_sec ) {
							if ( $value_sec['id'] == $value['id'] ) {
								$section = $value_sec;
								unset( $all_sections[ $key_sec ] );
								break;
							}
						}

						if ( empty( $section ) ) {
							$wpdb->insert( $wpdb->prefix . "zndskhc_sections",
								array( 'id' 			=> $value['id'],
										'category_id'	=> $value['category_id'],
										'position' 		=> $value['position'],
										'updated_at'	=> $value['updated_at'],
										'name'			=> $value['name'],
										'description'	=> $value['description'],
										'locale'		=> $value['locale'],
										'source_locale'	=> $value['source_locale'] ),
								array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
							$added++;
						} elseif ( strtotime( $section['updated_at'] ) < strtotime( $value['updated_at'] ) ) {
							$wpdb->update( $wpdb->prefix . "zndskhc_sections",
								array( 'category_id'	=> $value['category_id'],
										'position' 		=> $value['position'],
										'updated_at'	=> $value['updated_at'],
										'name'			=> $value['name'],
										'description'	=> $value['description'],
										'locale'		=> $value['locale'],
										'source_locale'	=> $value['source_locale'] ),
								array( 'id' => $value['id'] ),
								array(  '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
							$updated++;
						}
					}

					if ( empty( $array_resp['next_page'] ) ) {
						break;
					}
				}
				$i++;
			}

			if ( ! empty( $all_sections ) ) {
				foreach ( $all_sections as $key_sec => $value_sec ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_sections` WHERE `id` = %s", $value_sec['id'] ) );
					$deleted++;
				}
			}

			if ( $added != 0 || $updated != 0 || $deleted != 0 ) {
				$log = __( 'Sections backup', 'zendesk-help-center' ) . ':';
				if ( $added != 0 ) {
					$log .= ' ' . $added . ' ' . __( 'added', 'zendesk-help-center' ) . ';';
				}
				if ( $updated != 0 ) {
					$log .= ' ' . $updated . ' ' . __( 'updated', 'zendesk-help-center' ) . ';';
				}
				if ( $deleted != 0 ) {
					$log .= ' ' . $deleted . ' ' . __( 'deleted', 'zendesk-help-center' ) . ';';
				}
				zndskhc_log( $log );
			}
		}

		/* get articles */
		if ( ! empty( $zndskhc_options['backup_elements']['articles'] ) ) {
			$all_articles = $wpdb->get_results( "SELECT `id`, `updated_at` FROM `" . $wpdb->prefix . "zndskhc_articles`", ARRAY_A );
			$added = $updated = $deleted = 0;
			$added_comment = $updated_comment = $deleted_comment = 0;
			$added_attach = $updated_attach = $deleted_attach = 0;
			$attachments_backup_error = '';

			$i = 1;
			while ( $i != false ) {
				$url = 'https://' . $zndskhc_options['subdomain'] . '.zendesk.com/api/v2/help_center/articles.json?page=' . $i . '&per_page=30';
				$array_resp = zndskhc_remote_get( $url );

				if ( ! is_array( $array_resp ) && empty( $array_resp ) ) {
					$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Articles backup', 'zendesk-help-center' ) . ' - ' .  __( 'Undefined error has occurred while getting data from Zendesk API.', 'zendesk-help-center' );
					zndskhc_log( $log );
					if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
						zndskhc_send_mail( $log );
					}
					return $log;
				}

				if ( isset( $array_resp['error'] ) ) {
					$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Articles backup', 'zendesk-help-center' ) . ' - ' . $array_resp['error'] . ' (' . $array_resp['description'] . ')';
					zndskhc_log( $log );
					if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
						zndskhc_send_mail( $log );
					}
					return $log;
				} else {

					foreach ( $array_resp["articles"] as $key => $value ) {
						if ( $value['draft'] != true ) {

							$article = $attachments_backup_error_current = $new_or_updated = false;

							foreach ( $all_articles as $key_art => $value_art ) {
								if ( $value_art['id'] == $value['id'] ) {
									$article = $value_art;
									unset( $all_articles[ $key_art ] );
									break;
								}
							}

							if ( ! isset( $value['category_id'] ) ) {
								$value['category_id'] = 0;
							}
							if ( ! isset( $value['labels'] ) ) {
								$value['labels'] = '';
							}

							if ( empty( $article ) ) {
								$wpdb->insert( $wpdb->prefix . "zndskhc_articles",
									array( 'id' 				=> $value['id'],
											'category_id'		=> $value['category_id'],
											'section_id'		=> $value['section_id'],
											'position' 			=> $value['position'],
											'author_id' 		=> $value['author_id'],
											'comments_disabled'	=> $value['comments_disabled'],
											'promoted'			=> $value['promoted'],
											'name'				=> $value['name'],
											'title'				=> $value['title'],
											'body'				=> $value['body'],
											'locale'			=> $value['locale'],
											'source_locale'		=> $value['source_locale'],
											'labels'			=> $value['labels'] ),
									array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
								$added++;
								$new_or_updated = true;
							} elseif ( strtotime( $article['updated_at'] ) < strtotime( $value['updated_at'] ) ) {
								$wpdb->update( $wpdb->prefix . "zndskhc_articles",
									array( 'category_id'		=> $value['category_id'],
											'section_id'		=> $value['section_id'],
											'position' 			=> $value['position'],
											'comments_disabled'	=> $value['comments_disabled'],
											'promoted'			=> $value['promoted'],
											'name'				=> $value['name'],
											'title'				=> $value['title'],
											'body'				=> $value['body'],
											'locale'			=> $value['locale'],
											'source_locale'		=> $value['source_locale'],
											'labels'			=> $value['labels'] ),
									array( 'id' => $value['id'] ),
									array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
								$updated++;
								$new_or_updated = true;
							}

							/* get articles comments */
							if ( $new_or_updated && ! empty( $zndskhc_options['backup_elements']['comments'] ) ) {
								$all_article_comments = $wpdb->get_results( "SELECT `id`, `updated_at` FROM `" . $wpdb->prefix . "zndskhc_comments` WHERE `source_id` = '" . $value['id'] . "' AND `source_type` = 'Article'", ARRAY_A );
								$product_url_stat = 'https://' . $zndskhc_options['subdomain'] . '.zendesk.com/api/v2/help_center/articles/' . $value['id'] . '/comments.json';
								$array_resp_comments = zndskhc_remote_get( $product_url_stat );

								if ( !is_array( $array_resp_comments ) && empty( $array_resp_comments ) ) {
									$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Comments backup', 'zendesk-help-center' ) . ' - ' .  __( 'Undefined error has occurred while getting data from Zendesk API.', 'zendesk-help-center' );
									zndskhc_log( $log );
									if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
										zndskhc_send_mail( $log );
									}
									return $log;
								}

								if ( isset( $array_resp_comments['error'] ) ) {
									$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Comments backup', 'zendesk-help-center' ) . ' - ' . $array_resp_comments['error'] . ' (' . $array_resp_comments['description'] . ')';
									zndskhc_log( $log );
									if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
										zndskhc_send_mail( $log );
									}
									return $log;
								} else {
									foreach ( $array_resp_comments["comments"] as $comments_key => $comments_value ) {

										$comment = false;
										foreach ( $all_article_comments as $key_comment => $value_comment ) {
											if ( $value_comment['id'] == $value['id'] ) {
												$comment = $value_comment;
												unset( $all_article_comments[ $key_comment ] );
												break;
											}
										}

										if ( empty( $comment ) ) {
											$wpdb->insert( $wpdb->prefix . "zndskhc_comments",
												array( 'id' 				=> $comments_value['id'],
														'author_id' 		=> $comments_value['author_id'],
														'source_type' 		=> $comments_value['source_type'],
														'source_id' 		=> $comments_value['source_id'],
														'body'				=> $comments_value['body'],
														'locale'			=> $comments_value['locale'],
														'updated_at'		=> $comments_value['updated_at'] ),
												array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
											$added_comment++;
										} elseif ( strtotime( $comment['updated_at'] ) < strtotime( $comments_value['updated_at'] ) ) {
											$wpdb->update( $wpdb->prefix . "zndskhc_comments",
												array( 'author_id'			=> $comments_value['author_id'],
														'source_type'		=> $comments_value['source_type'],
														'source_id' 		=> $comments_value['source_id'],
														'body'				=> $comments_value['body'],
														'locale'			=> $comments_value['locale'],
														'updated_at'		=> $comments_value['updated_at'] ),
												array( 'id' => $comments_value['id'] ),
												array( '%s', '%s', '%s', '%s', '%s', '%s' ) );
											$updated_comment++;
										}
									}
									if ( ! empty( $all_article_comments ) ) {
										foreach ( $all_article_comments as $key_comment => $value_comment ) {
											$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_comments` WHERE `id` = %s AND `source_type` = 'Article'", $value_comment['id'] ) );
											$deleted_comment++;
										}
									}
								}
							}

							/* get attachments */
							if ( $new_or_updated && ! empty( $zndskhc_options['backup_elements']['attachments'] ) ) {
								$all_attachments = $wpdb->get_results( "SELECT `id`, `updated_at`, `file_name` FROM `" . $wpdb->prefix . "zndskhc_attachments` WHERE `article_id`='" . $value['id'] . "'", ARRAY_A );
								$k = 1;
								while ( $k != false ) {
									$product_url_stat = 'https://' . $zndskhc_options['subdomain'] . '.zendesk.com/api/v2/help_center/articles/' . $value['id'] . '/attachments.json?page=' . $k . '&per_page=30';
									$array_resp_attach = zndskhc_remote_get( $product_url_stat );

									if ( !is_array( $array_resp_attach ) && empty( $array_resp_attach ) ) {
										$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Attachments backup', 'zendesk-help-center' ) . ' - ' .  __( 'Undefined error has occurred while getting data from Zendesk API.', 'zendesk-help-center' );
										zndskhc_log( $log );
										if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
											zndskhc_send_mail( $log );
										}
										return $log;
									}

									if ( isset( $array_resp_attach['error'] ) ) {
										$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Attachments backup', 'zendesk-help-center' ) . ' - ' . $array_resp_attach['error'] . ' (' . $array_resp_attach['description'] . ')';
										zndskhc_log( $log );
										if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
											zndskhc_send_mail( $log );
										}
										return $log;
									} else {
										foreach ( $array_resp_attach["article_attachments"] as $attach_key => $attach_value ) {
											$attachment = false;
											foreach ( $all_attachments as $key_attach => $value_attach ) {
												if ( $value_attach['id'] == $attach_value['id'] ) {
													$attachment = $value_attach;
													unset( $all_attachments[ $key_attach ] );
													break;
												}
											}

											if ( empty( $attachment ) ) {
												$result = zndskhc_attachments_backup( 'added', $attach_value['id'] . '-' . $attach_value['file_name'], $attach_value['content_url'] );
												if ( $result != true ) {
													$attachments_backup_error_current = true;
													$attachments_backup_error .= __( 'Error adding file', 'zendesk-help-center' ) . ' ' . $attach_value['id'] . '-' . $attach_value['file_name'] . '. ';
												} else {
													$wpdb->insert( $wpdb->prefix . "zndskhc_attachments",
														array( 'id' 			=> $attach_value['id'],
																'url'			=> $attach_value['url'],
																'article_id'	=> $attach_value['article_id'],
																'file_name'		=> $attach_value['file_name'],
																'content_url'	=> $attach_value['content_url'],
																'content_type'	=> $attach_value['content_type'],
																'size'			=> $attach_value['size'],
																'inline'		=> $attach_value['inline'],
																'created_at'	=> $attach_value['created_at'],
																'updated_at'	=> $attach_value['updated_at'] ),
														array( '%s', '%s', '%s' ) );
													$added_attach++;
												}
											} elseif ( strtotime( $attachment['updated_at'] ) < strtotime( $attach_value['updated_at'] ) ) {
												$result = zndskhc_attachments_backup( 'updated', $attach_value['id'] . '-' . $attach_value['file_name'], $attach_value['content_url'] );
												if ( $result != true ) {
													$attachments_backup_error_current = true;
													$attachments_backup_error .= __( 'Error updating file', 'zendesk-help-center' ) . ' ' . $attach_value['id'] . '-' . $attach_value['file_name'] . '. ';
												} else {
													$wpdb->update( $wpdb->prefix . "zndskhc_attachments",
														array( 'url'			=> $attach_value['url'],
																'article_id'	=> $attach_value['article_id'],
																'file_name'		=> $attach_value['file_name'],
																'content_url'	=> $attach_value['content_url'],
																'content_type'	=> $attach_value['content_type'],
																'size'			=> $attach_value['size'],
																'inline'		=> $attach_value['inline'],
																'created_at'	=> $attach_value['created_at'],
																'updated_at'	=> $attach_value['updated_at'] ),
														array( 'id' => $attach_value['id'] ),
														array(  '%s', '%s' ) );
													$updated_attach++;
												}
											}
										}

										if ( empty( $array_resp_comments['next_page'] ) ) {
											break;
										}
									}
									$k++;
								}
							}

							if ( ! empty( $all_attachments ) ) {
								foreach ( $all_attachments as $key_attach => $value_attach ) {
									$result = zndskhc_attachments_backup( 'deleted', $value_attach['id'] . '-' . $value_attach['file_name'] );
									if ( $result != true ) {
										$attachments_backup_error_current = true;
										$attachments_backup_error .= __( 'Error deleting file', 'zendesk-help-center' ) . ' ' . $value_attach['id'] . '-' . $value_attach['file_name'] . '. ';
									} else {
										$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_attachments` WHERE `id` = %s", $value_attach['id'] ) );
										$deleted_attach++;
									}
								}
							}

							if ( ! $attachments_backup_error_current ) {
								$wpdb->update( $wpdb->prefix . "zndskhc_articles",
										array( 'updated_at'		=> $value['updated_at'] ),
										array( 'id' => $value['id'] ),
										array( '%s' ) );
							}
						}
					}

					if ( empty( $array_resp['next_page'] ) ) {
						break;
					}
				}
				$i++;
			}

			if ( ! empty( $all_articles ) ) {
				foreach ( $all_articles as $key_art => $value_art ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_articles` WHERE `id` = %s", $value_art['id'] ) );
					$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_comments` WHERE `source_id` = %s AND `source_type` = 'Article'", $value_art['id'] ) );
					$deleted++;
				}
			}

			if ( $added != 0 || $updated != 0 || $deleted != 0 ) {
				$log = __( 'Articles backup', 'zendesk-help-center' ) . ':';
				if ( $added != 0 ) {
					$log .= ' ' . $added . ' ' . __( 'added', 'zendesk-help-center' ) . ';';
				}
				if ( $updated != 0 ) {
					$log .= ' ' . $updated . ' ' . __( 'updated', 'zendesk-help-center' ) . ';';
				}
				if ( $deleted != 0 ) {
					$log .= ' ' . $deleted . ' ' . __( 'deleted', 'zendesk-help-center' ) . ';';
				}
				zndskhc_log( $log );
			}
			if ( $added_comment != 0 || $updated_comment != 0 || $deleted_comment != 0 ) {
				$log = __( 'Comments backup', 'zendesk-help-center' ) . ':';
				if ( $added_comment != 0 ) {
					$log .= ' ' . $added_comment . ' ' . __( 'added', 'zendesk-help-center' ) . ';';
				}
				if ( $updated_comment != 0 ) {
					$log .= ' ' . $updated_comment . ' ' . __( 'updated', 'zendesk-help-center' ) . ';';
				}
				if ( $deleted_comment != 0 ) {
					$log .= ' ' . $deleted_comment . ' ' . __( 'deleted', 'zendesk-help-center' ) . ';';
				}
				zndskhc_log( $log );
			}
			if ( ! empty( $attachments_backup_error ) ) {
				$upload_dir = wp_upload_dir();
				$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Attachments backup', 'zendesk-help-center' ) . ' ( ' . $upload_dir['basedir'] . '/zendesk_hc_attachments/' . ' ) - ' . $attachments_backup_error;
				zndskhc_log( $log );
				if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
					zndskhc_send_mail( $log );
				}
			}
			if ( $added_attach != 0 || $updated_attach != 0 || $deleted_attach != 0 ) {
				$log = __( 'Attachments backup', 'zendesk-help-center' ) . ':';
				if ( $added_attach != 0 ) {
					$log .= ' ' . $added_attach . ' ' . __( 'added', 'zendesk-help-center' ) . ';';
				}
				if ( $updated_attach != 0 ) {
					$log .= ' ' . $updated_attach . ' ' . __( 'updated', 'zendesk-help-center' ) . ';';
				}
				if ( $deleted_attach != 0 ) {
					$log .= ' ' . $deleted_attach . ' ' . __( 'deleted', 'zendesk-help-center' ) . ';';
				}
				zndskhc_log( $log );
			}
		}

		/* get labels */
		if ( ! empty( $zndskhc_options['backup_elements']['labels'] ) ) {
			$all_labels = $wpdb->get_results( "SELECT `id`, `updated_at` FROM `" . $wpdb->prefix . "zndskhc_labels`", ARRAY_A );
			$url = 'https://' . $zndskhc_options['subdomain'] . '.zendesk.com/api/v2/help_center/articles/labels.json';
			$array_resp = zndskhc_remote_get( $url );
			if ( !is_array( $array_resp ) && empty( $array_resp ) ) {
				$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Labels backup', 'zendesk-help-center' ) . ' - ' .  __( 'Undefined error has occurred while getting data from Zendesk API.', 'zendesk-help-center' );
				zndskhc_log( $log );
				if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
					zndskhc_send_mail( $log );
				}
				return $log;
			}

			if ( isset( $array_resp['error'] ) ) {
				if ( 'RecordNotFound' == $array_resp['error'] ) {
					$log = __( 'WARNING', 'zendesk-help-center' ) . ': ' . __( 'Labels backup', 'zendesk-help-center' ) . ' - ' . $array_resp['error'] . ' (' . $array_resp['description'] . ')';
					zndskhc_log( $log );
					return $log;
				} else {
					$log = __( 'ERROR', 'zendesk-help-center' ) . ': ' . __( 'Labels backup', 'zendesk-help-center' ) . ' - ' . $array_resp['error'] . ' (' . $array_resp['description'] . ')';
					zndskhc_log( $log );
					if ( $auto_mode && ! empty( $zndskhc_options['emailing_fail_backup'] ) ) {
						zndskhc_send_mail( $log );
					}
					return $log;
				}
			} else {
				$added = $updated = $deleted = 0;
				foreach ( $array_resp["labels"] as $key => $value ) {
					$label = false;
					foreach ( $all_labels as $key_label => $value_label ) {
						if ( $value_label['id'] == $value['id'] ) {
							$label = $value_label;
							unset( $all_labels[ $key_label ] );
							break;
						}
					}

					if ( empty( $label ) ) {
						$wpdb->insert( $wpdb->prefix . "zndskhc_labels",
							array( 'id' 			=> $value['id'],
									'name'			=> $value['name'],
									'updated_at'	=> $value['updated_at'] ),
							array( '%s', '%s', '%s' ) );
						$added++;
					} elseif ( strtotime( $label['updated_at'] ) < strtotime( $value['updated_at'] ) ) {
						$wpdb->update( $wpdb->prefix . "zndskhc_labels",
							array( 'updated_at'	=> $value['updated_at'],
									'name'			=> $value['name'] ),
							array( 'id' => $value['id'] ),
							array(  '%s', '%s' ) );
						$updated++;
					}
				}
				if ( ! empty( $all_labels ) ) {
					foreach ( $all_labels as $key_label => $value_label ) {
						$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_labels` WHERE `id` = %s", $value_label['id'] ) );
						$deleted++;
					}
				}
				if ( $added != 0 || $updated != 0 || $deleted != 0 ) {
					$log = __( 'Labels backup', 'zendesk-help-center' ) . ':';
					if ( $added != 0 ) {
						$log .= ' ' . $added . ' ' . __( 'added', 'zendesk-help-center' ) . ';';
					}
					if ( $updated != 0 ) {
						$log .= ' ' . $updated . ' ' . __( 'updated', 'zendesk-help-center' ) . ';';
					}
					if ( $deleted != 0 ) {
						$log .= ' ' . $deleted . ' ' . __( 'deleted', 'zendesk-help-center' ) . ';';
					}
					zndskhc_log( $log );
				}
			}
		}

		$zndskhc_options['last_synch'] = current_time( 'mysql' );
		update_option( 'zndskhc_options', $zndskhc_options );
		return true;
	}
}

/* attachment deleted/added/updated */
if ( ! function_exists( 'zndskhc_attachments_backup' ) ) {
	function zndskhc_attachments_backup( $status, $filename, $content_url = false ) {
		$upload_dir = wp_upload_dir();
		if ( ! $upload_dir["error"] ) {
			$cstm_folder = $upload_dir['basedir'] . '/zendesk_hc_attachments';
			if ( ! is_dir( $cstm_folder ) ) {
				wp_mkdir_p( $cstm_folder, 0755 );
			}
		}
		$uploadfile = $cstm_folder . '/' . $filename;

		if ( 'deleted' == $status ) {
			if ( ! file_exists( $uploadfile ) ) {
				return true;
			}
			if ( unlink( $uploadfile ) ) {
				return true;
			}
		} else if ( 'added' == $status || 'updated' == $status ) {
			if ( $file_get_contents = file_get_contents( $content_url ) ) {
				if ( file_put_contents( $uploadfile, $file_get_contents ) ) {
					return true;
				}
			}
		}
		return false;
	}
}

/* Add log to the file */
if ( ! function_exists( 'zndskhc_log' ) ) {
	function zndskhc_log( $log ) {
		$log = date( 'd.m.Y h:i:s' ) . '	' . $log . "\n";
		@error_log( $log, 3, dirname( __FILE__ )  . "/backup.log" );
		@chmod( dirname( __FILE__ )  . "/backup.log", 0755 );
	}
}

/* Get last logs */
if ( ! function_exists( 'zndskhc_get_logs' ) ) {
	function zndskhc_get_logs() {
		$content = file_get_contents( dirname( __FILE__ )  . "/backup.log" );
		if ( ! empty( $content ) ) {
			echo '<h3>' . __( 'Last log entries', 'zendesk-help-center' ) . ':</h3>';
			$content_array = explode( "\n", $content );
			if ( is_array( $content_array ) ) {
				$content_reverse = array_reverse( $content_array );
				$i = 0;
				foreach ( $content_reverse as $key => $value ) {
					if ( $i < 12 ) {
						echo '<div';
						if ( false != strpos( $value, __( 'ERROR', 'zendesk-help-center' ) ) ) {
							echo ' class="zndskhc_error_log"';
						}
						if ( false != strpos( $value, __( 'WARNING', 'zendesk-help-center' ) ) ) {
							echo ' class="zndskhc_warning_log"';
						}
						echo '>' . $value . '</div>';
						$i++;
					} else {
						break;
					}
				}
			} else {
				echo $content;
			}
		}
	}
}

/* Get last logs */
if ( ! function_exists( 'zndskhc_send_mail' ) ) {
	function zndskhc_send_mail( $message ) {
		global $zndskhc_options;
		if ( empty( $zndskhc_options ) ) {
			$zndskhc_options = get_option( 'zndskhc_options' );
		}

		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}
		$from_email = 'wordpress@' . $sitename;

		/* send message to user */
		$headers = 'From: ' . get_bloginfo( 'name' ) . ' <' . $from_email . '>';
		$subject = __( "Help Center backup error on", 'zendesk-help-center' ) . ' ' . esc_attr( get_bloginfo( 'name', 'display' ) );;
		wp_mail( $zndskhc_options['email'], $subject, $message, $headers );
	}
}

/* Add time for cron viev */
if ( ! function_exists( 'zndskhc_schedules' ) ) {
	function zndskhc_schedules( $schedules ) {
		global $zndskhc_options;
		if ( empty( $zndskhc_options ) ) {
			$zndskhc_options = get_option( 'zndskhc_options' );
		}
		$schedules_hours = ( '' != $zndskhc_options['time'] ) ? $zndskhc_options['time'] : 48;

		$schedules['schedules_hours'] = array( 'interval' => $schedules_hours*60*60, 'display' => 'Every ' . $schedules_hours . ' hours' );
		return $schedules;
	}
}

/* Positioning in the page. End. */
if ( !function_exists( 'zndskhc_action_links' ) ) {
	function zndskhc_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			/* Static so we don't call plugin_basename on every plugin row. */
			static $this_plugin;
			if ( ! $this_plugin ) $this_plugin = plugin_basename( __FILE__ );

			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=zendesk_hc.php">' . __( 'Settings', 'zendesk-help-center' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
} /* End function zndskhc_action_links */

/* Function are using to create link 'settings' on admin page. */
if ( !function_exists( 'zndskhc_links' ) ) {
	function zndskhc_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			if ( ! is_network_admin() ) {
				$links[]	=	'<a href="admin.php?page=zendesk_hc.php">' . __( 'Settings', 'zendesk-help-center' ) . '</a>';
			}
			$links[]	=	'<a href="https://support.bestwebsoft.com/hc/en-us/sections/200956739" target="_blank">' . __( 'FAQ', 'zendesk-help-center' ) . '</a>';
			$links[]	=	'<a href="https://support.bestwebsoft.com">' . __( 'Support', 'zendesk-help-center' ) . '</a>';
		}
		return $links;
	}
}

if ( ! function_exists( 'zndskhc_admin_js' ) ) {
	function zndskhc_admin_js() {
		wp_enqueue_style( 'zndskhc_icon', plugins_url( 'css/icon.css', __FILE__ ) );

		if ( isset( $_REQUEST['page'] ) && ( 'zendesk_hc.php' == $_REQUEST['page'] || 'zendesk_hc_backup.php' == $_REQUEST['page'] ) ) {
			wp_enqueue_style( 'zndskhc_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
			wp_enqueue_script( 'zndskhc_script', plugins_url( 'js/script.js', __FILE__ ) );

			bws_plugins_include_codemirror();
			bws_enqueue_settings_scripts();
		}
	}
}

/* add admin notices */
if ( ! function_exists ( 'zndskhc_admin_notices' ) ) {
	function zndskhc_admin_notices() {
		global $hook_suffix, $zndskhc_plugin_info;

		if ( 'plugins.php' == $hook_suffix && ! is_network_admin() ) {
			bws_plugin_banner_to_settings( $zndskhc_plugin_info, 'zndskhc_options', 'zendesk-help-center', 'admin.php?page=zendesk_hc.php&action=settings' );			
		}

		if ( isset( $_REQUEST['page'] ) && 'zendesk_hc.php' == $_REQUEST['page'] ) {
			bws_plugin_suggest_feature_banner( $zndskhc_plugin_info, 'zndskhc_options', 'zendesk-help-center' );
		}
	}
}

/* add help tab  */
if ( ! function_exists( 'zndskhc_add_tabs' ) ) {
	function zndskhc_add_tabs() {
		$screen = get_current_screen();
		$args = array(
			'id' 			=> 'zndskhc',
			'section' 		=> '200956739'
		);
		bws_help_tab( $screen, $args );
	}
}

/* Function for delete options from table `wp_options` */
if ( ! function_exists( 'delete_zndskhc_settings' ) ) {
	function delete_zndskhc_settings() {
		global $wpdb;
		delete_option( 'zndskhc_options' );
		/* delete plugin`s tables */
		$wpdb->query( "DROP TABLE IF EXISTS
			`" . $wpdb->prefix . "zndskhc_categories`,
			`" . $wpdb->prefix . "zndskhc_sections`,
			`" . $wpdb->prefix . "zndskhc_articles`,
			`" . $wpdb->prefix . "zndskhc_labels`,
			`" . $wpdb->prefix . "zndskhc_comments`,
			`" . $wpdb->prefix . "zndskhc_attachments`;"
		);
		/* delete plugin`s upload_dir */
		$upload_dir = wp_upload_dir();
		if ( ! $upload_dir["error"] ) {
			$cstm_folder = $upload_dir['basedir'] . '/zendesk_hc_attachments';
			if ( is_dir( $cstm_folder ) ) {
				rmdir( $cstm_folder );
			}
		}
		/* Delete hook if it exist */
		wp_clear_scheduled_hook( 'auto_synchronize_zendesk_hc' );
	}
}

if ( ! function_exists( 'zndskhc_plugin_uninstall' ) ) {
	function zndskhc_plugin_uninstall() {
		global $wpdb;

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$all_plugins = get_plugins();
		if ( ! array_key_exists( 'zendesk-help-center-pro/zendesk-help-center-pro.php', $all_plugins ) ) {
			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				$old_blog = $wpdb->blogid;
				/* Get all blog ids */
				$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );
					delete_zndskhc_settings();
				}
				switch_to_blog( $old_blog );
			} else {
				delete_zndskhc_settings();
			}
		}

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

register_activation_hook( __FILE__, 'zndskhc_activation_hook' );

add_action( 'admin_menu', 'add_zndskhc_admin_menu' );
add_action( 'init', 'zndskhc_init' );
add_action( 'admin_init', 'zndskhc_admin_init' );
add_action( 'plugins_loaded', 'zndskhc_plugins_loaded' );

add_action( 'admin_enqueue_scripts', 'zndskhc_admin_js' );

/* Add time for cron viev */
add_filter( 'cron_schedules', 'zndskhc_schedules' );
/* Function that update all plugins and WP core in auto mode. */
add_action( 'auto_synchronize_zendesk_hc', 'zndskhc_synchronize' );

/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'zndskhc_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'zndskhc_links', 10, 2 );
/* add admin notices */
add_action( 'admin_notices', 'zndskhc_admin_notices' );

register_uninstall_hook( __FILE__, 'zndskhc_plugin_uninstall' );