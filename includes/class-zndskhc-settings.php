<?php
/**
 * Displays the content on the plugin settings page
 */
if ( ! class_exists( 'Zndskhc_Settings_Tabs' ) ) {
	class Zndskhc_Settings_Tabs extends Bws_Settings_Tabs {
		private $file_log;
		/**
		 * Constructor.
		 *
		 * @access public
		 *
		 * @see Bws_Settings_Tabs::__construct() for more information on default arguments.
		 *
		 * @param string $plugin_basename
		 */
		public function __construct( $plugin_basename ) {
			global $zndskhc_options, $zndskhc_plugin_info;

			$tabs = array(
				'settings' 		=> array( 'label' => __( 'Settings', 'zendesk-help-center' ) ),
				'misc' 			=> array( 'label' => __( 'Misc', 'zendesk-help-center' ) ),
				'custom_code' 	=> array( 'label' => __( 'Custom Code', 'zendesk-help-center' ) ),
				'license'		=> array( 'label' => __( 'License Key', 'zendesk-help-center' ) )
			);

			parent::__construct( array(
				'plugin_basename' 	 => $plugin_basename,
				'plugins_info'		 => $zndskhc_plugin_info,
				'prefix' 			 => 'zndskhc',
				'default_options' 	 => zndskhc_get_options_default(),
				'options' 			 => $zndskhc_options,
				'is_network_options' => is_network_admin(),
				'tabs' 				 => $tabs,
				'wp_slug'			 => 'zendesk-help-center',
				'link_key' 			 => '036b375477a35a960f966d052591e9ed',
				'link_pn' 			 => '208',
				'doc_link'          => 'https://bestwebsoft.com/documentation/help-center/help-center-user-guide/'
			) );

			$this->file_log = dirname( dirname( __FILE__ ) )  . "/backup.log";
		}

		/**
		 * Save plugin options to the database
		 * @access public
		 * @param  void
		 * @return array    The action results
		 */
		public function save_options() {
			global $zndskhc_lang_codes;

			$message = $notice = $error = '';

			if ( isset( $_REQUEST['zndskhc_submit_clear'] ) ) {
				if ( $handle = fopen( $this->file_log, "w" ) ) {
					fwrite( $handle, '' );
					fclose( $handle );
					@chmod( $this->file_log, 0755 );
					$message = __( "The log file is cleared." , 'zendesk-help-center' );
				} else {
					$error = __( "Couldn't clear log file." , 'zendesk-help-center' );
				}
			} else {

				$this->options['subdomain'] = stripslashes( sanitize_text_field( $_REQUEST['zndskhc_subdomain'] ) );
				$this->options['user'] 		= sanitize_user( $_REQUEST['zndskhc_user'] );
				$this->options['password'] 	= stripslashes( sanitize_text_field( $_REQUEST['zndskhc_password'] ) );
				$this->options['token'] 	= stripslashes( sanitize_text_field( $_REQUEST['zndskhc_token'] ) );
				
				if ( $this->options['time'] != intval( $_REQUEST['zndskhc_time'] ) ) {
					$this->options['time'] = intval( $_REQUEST['zndskhc_time'] );
					/* Add or delete hook of auto/handle mode */
					if ( wp_next_scheduled( 'auto_synchronize_zendesk_hc' ) ) {
						wp_clear_scheduled_hook( 'auto_synchronize_zendesk_hc' );
					}

					if ( '0' != $this->options['time'] ) {
						$time = time() + $this->options['time']*60*60;
						wp_schedule_event( $time, 'schedules_hours', 'auto_synchronize_zendesk_hc' );
					}
				}
				$this->options['backup_elements']['categories'] = ( isset( $_REQUEST['zndskhc_categories_backup'] ) ) ? 1 : 0;
				$this->options['backup_elements']['sections'] = ( isset( $_REQUEST['zndskhc_sections_backup'] ) ) ? 1 : 0;
				$this->options['backup_elements']['articles'] = ( isset( $_REQUEST['zndskhc_articles_backup'] ) ) ? 1 : 0;
				$this->options['backup_elements']['comments'] = ( isset( $_REQUEST['zndskhc_comments_backup'] ) && isset( $_REQUEST['zndskhc_articles_backup'] ) ) ? 1 : 0;
				$this->options['backup_elements']['labels'] = ( isset( $_REQUEST['zndskhc_labels_backup'] ) ) ? 1 : 0;
				$this->options['backup_elements']['attachments'] = ( isset( $_REQUEST['zndskhc_attachments_backup'] ) && isset( $_REQUEST['zndskhc_articles_backup'] ) ) ? 1 : 0;

				$this->options['emailing_fail_backup'] = isset( $_REQUEST['zndskhc_emailing_fail_backup'] ) ? 1 : 0;
				$this->options['email'] = sanitize_email( $_REQUEST['zndskhc_email'] );
				
				if ( ! is_email( $this->options['email'] ) ) {
					$this->options['email'] = $this->default_options['email'];
				}

				update_option( 'zndskhc_options', $this->options );
				$message = __( "Settings saved" , 'zendesk-help-center' );
			}

			return compact( 'message', 'notice', 'error' );
		}

		/**
		 *
		 */
		public function tab_settings() { 
			$elements = array(
				'categories' 	=> __( 'Categories' , 'zendesk-help-center' ),
				'sections' 		=> __( 'Sections' , 'zendesk-help-center' ),
				'articles' 		=> __( 'Articles' , 'zendesk-help-center' ),
				'comments' 		=> __( 'Articles Comments' , 'zendesk-help-center' ),
				'labels' 		=> __( 'Articles Labels' , 'zendesk-help-center' ),
				'attachments' 	=> __( 'Articles Attachments' , 'zendesk-help-center' )
			); 
			if ( ! file_exists( $this->file_log ) ) {
				if ( $handle = @fopen( $this->file_log, "w+" ) ) {
					$log_size = 0;
					fclose( $handle );
				} else {
					$log_error = __( "Error creating log file" , 'zendesk-help-center' ) . ': ' . $this->file_log . '.';
				}
			}
			if ( file_exists( $this->file_log ) ) {
				$log_size = round( filesize( $this->file_log ) / 1024, 2 );
			} ?>
			<h3 class="bws_tab_label"><?php _e( 'Help Center Settings', 'zendesk-help-center' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr />
			<div class="bws_tab_sub_label"><?php _e( 'Zendesk Authorization', 'zendesk-help-center' ); ?>
			</div>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Zendesk Information', 'zendesk-help-center' ); ?></th>
					<td>
						<input type="text" maxlength='250' class="zndskhc_input_field" name="zndskhc_subdomain" value="<?php echo $this->options['subdomain']; ?>" />
						<?php _e( 'subdomain', 'zendesk-help-center' ); ?>
						<?php echo bws_add_help_box( sprintf(
							__( "Example: your URL is %s, and it is necessary to enter %s part only.", 'zendesk-help-center' ),
							'<i>https://mysubdomain.zendesk.com</i>',
							'<i>mysubdomain</i>'
						) ); ?>
						<br />
						<input type="text" maxlength='250' class="zndskhc_input_field" name="zndskhc_user" value="<?php echo $this->options['user']; ?>" /> <?php _e( 'email', 'zendesk-help-center' ); ?><br />
						<input type="password" maxlength='250' class="zndskhc_input_field" name="zndskhc_password" value="<?php echo $this->options['password']; ?>" /> <?php _e( 'password', 'zendesk-help-center' ); ?><br />
						<input type="text" maxlength='250' class="zndskhc_input_field" name="zndskhc_token" value="<?php echo $this->options['token']; ?>" /> <?php _e( 'token', 'zendesk-help-center' ); ?>
						<p class="bws_info"><?php _e( 'Don\'t know how to generate API token? ', 'zendesk-help-center' );?> <a href="https://support.bestwebsoft.com/hc/en-us/articles/115005881386" target="_new" ><?php _e( "Read the instruction", 'bestwebsoft' ); ?></a></p>
					</td>
				</tr>
			</table>
			<div class="bws_tab_sub_label"><?php _e( 'General', 'zendesk-help-center' ); ?>
			</div>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Log File Size', 'zendesk-help-center' ); ?></th>
					<td>
						<?php if ( ! empty( $log_error ) ) { ?>
							<div class="error below-h2"><p><?php echo $log_error; ?></p></div>
						<?php } else { ?>
							&#126; <?php echo $log_size . ' ' . __( 'Kbyte', 'zendesk-help-center' ); ?>
							<?php if ( 0 != $log_size ) { ?>
								&#160;&#160;&#160;<input name="zndskhc_submit_clear" type="submit" class="button button-secondary" value="<?php _e( 'Clear', 'zendesk-help-center' ); ?>" />
							<?php } ?>
						<?php } ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Synchronize Every', 'zendesk-help-center' ); ?></th>
					<td>
						<input type="number" min="0" name="zndskhc_time" style="width: 100px;" value="<?php echo $this->options['time']; ?>" /> <?php _e( 'hours' , 'zendesk-help-center' ); ?>
						<p class="bws_info"><?php _e( 'Set 0 to disable auto backup.', 'zendesk-help-center' ); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Backup', 'zendesk-help-center' ); ?></th>
					<td><fieldset>
						<?php foreach ( $elements as $key => $value ) { ?>
							<label><input type="checkbox" name="zndskhc_<?php echo $key; ?>_backup" value="1" <?php if ( $this->options['backup_elements'][ $key ] ) echo 'checked'; ?> /> <?php echo $value; ?></label><br />
						<?php } ?>
					</fieldset></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Backup Failure Notification', 'zendesk-help-center' ); ?></th>
					<td>
						<input type="checkbox" name="zndskhc_emailing_fail_backup" value="1" <?php if ( $this->options['emailing_fail_backup'] ) echo 'checked'; ?> class="bws_option_affect" data-affect-show=".zndskhc_email_fail_backup" />
						<span class="bws_info"><?php _e( 'Enable to receive notification on backup failure.', 'zendesk-help-center' ); ?></span>
						<p class="zndskhc_email_fail_backup">
							<input type="email" maxlength='250' class="zndskhc_input_field" name="zndskhc_email" value="<?php echo $this->options['email']; ?>" />
						</p>
					</td>
				</tr>
			</table>
			<div class="bws_tab_sub_label"><?php _e( 'Help Widget', 'zendesk-help-center' ); ?>
			</div><br/>		
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'zendesk-help-center' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr valign="top">
								<th scope="row"><?php _e( 'Help Widget', 'zendesk-help-center' ); ?></th>
								<td>
									<input disabled="disabled" type="checkbox" name="zndskhc_display_help_widget" value="1" />
									<span class="bws_info"><?php _e( 'Enable to display help widget on the site.', 'zendesk-help-center' ); ?></span>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Button Link', 'zendesk-help-center' ); ?></th>
								<td>
									<input disabled="disabled" type="url" class="zndskhc_input_field" name="zndskhc_contact_link" value="" />
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Button Title', 'zendesk-help-center' ); ?></th>
								<td>
									<input disabled="disabled" type="text" class="zndskhc_input_field" name="zndskhc_help_button_title" value="" />
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php }
		}
	}
}