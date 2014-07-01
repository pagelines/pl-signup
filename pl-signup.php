<?php
/*
Plugin Name: PageLines Signup
Author: PageLines
Author URI: http://www.pagelines.com
Description: Adds a slider type email subscription button. Emails will be auto added to a MailChimp list using AJAX
Version: 0.2
PageLines: true
V3: true
Section: true
Class Name: DMS_Signup
Filter: social
*/

// early bail if were not in dms.
if( ! class_exists( 'PageLinesSection' ) )
  return;

class DMS_Signup extends PageLinesSection{

	function section_styles(){
		
		$ajax_url = admin_url( 'admin-ajax.php' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'dms-signup', $this->base_url.'/scripts.js', array( 'jquery' ), pl_get_cache_key(), false );
		wp_enqueue_script( 'dms-signup-fittext', $this->base_url.'/jquery.fittext.js', array( 'jquery' ), '1.2', false );
		

		if ( has_action( 'pl_force_ssl' ) )
			$ajax_url = str_replace( 'http://', 'https://', $ajax_url );
		
		
		
		wp_localize_script( 'dms-signup', 'dmssignupajax', array(
			'url'			=> $ajax_url,
			'replytxt'		=> pl_setting( 'dms_signup_success_txt', array( 'default' => "[email] was subscribed <i class='icon icon-smile'></i>" ) ),
			'sending'		=> pl_setting( 'dms_signup_sending_txt', array( 'default' => "<i class='icon icon-refresh icon-spin spin-fast'></i> Sending..." ) ),
			'slider'		=> $this->opt( 'dms_signup_subscribe_disable', array( 'default' => false ) )
			) );
	}

	function section_persistent() {
		add_action( 'wp_ajax_nopriv_pl_ajax_dms_subscribe'    , array( $this, 'subscribe' ) );
		add_action( 'wp_ajax_pl_ajax_dms_subscribe'           , array( $this, 'subscribe' ) );
		add_action( 'init'                                    , array( $this, 'get_emails' ) );
	}

	function section_opts() {
		$opts = array(
			array(
				'type'		=> 'multi',
				'title'		=> 'PageLines Signup Settings',
				'help'		=> sprintf( 'All emails will be saved to a WordPress option (dms_signup_emails)<br />If Mailchimp is enabled they will be sent there too.<br />To view the emails in a browser (CSV) click <a href="%s">HERE</a>',
								site_url( '?dms_signup_emails=1' )
								),
				'opts'		=> array(
					array(
						'key'		=> 'dms_signup_subscribe',
						'type'		=> 'text',
						'default'	=> 'Email Subscribe',
						'place'		=> 'Email Subscribe',
						'label'		=> 'Slide Button Text',
					),
					array(
						'key'		=> 'dms_signup_subscribe_disable',
						'type'		=> 'check',
						'default'	=> false,
						'label'		=> 'Disable Slide Button ( Just show email form )',
					),
					array(
						'key'		=> 'dms_signup_submit',
						'type'		=> 'text',
						'default'	=> 'Submit <i class="icon icon-chevron-sign-right"></i>',
						'place'		=> 'Submit <i class="icon icon-chevron-sign-right"></i>',
						'label'		=> 'Submit button text',
					),
					array(
						'key'		=> 'dms_signup_sending_txt',
						'type'		=> 'text',
						'label'		=> 'Text to show during send',
						'place'		=> "<i class='icon icon-refresh icon-spin spin-fast'></i> Sending...",
						'scope'		=> 'global'
					),
					array(
						'key'		=> 'dms_signup_success_txt',
						'type'		=> 'text',
						'place'		=> "[email] was subscribed <i class='icon icon-smile'></i>",
						'label'		=> 'Text to show on success',
						'scope'		=> 'global'
					),
					array(
						'key'		=> 'dms_submit_btn_theme',
						'type'		=> 'select_button',
						'label'		=> __( 'Submit Button Color', 'pagelines'),
					)
				)
			),
			array(
				'type'		=> 'multi',
				'title'		=> 'MailChimp Integration',
				'ref'		=> 'More than 6 million people use MailChimp to create, send, and track email newsletters. Whether you are self-employed, you manage projects for clients, or you work for a Fortune 500 company, MailChimp has features and integrations that will suit your email marketing needs.<br /><br />Create signup forms that match your brand\'s look and feel, and send your subscribers product updates, event invitations, announcements, or editorial content. Use our reports to improve your campaigns and learn more about your readers. Best of all? MailChimp is free for lists of up to 2,000 subscribers.<br /><br /><a target="_blank" href="http://eepurl.com/XT6lj">Click here to get a free account</a>',
				'col'		=> 2,
				'opts'		=> array(
					array(
						'key'		=> 'dms_signup_mailchimp_api',
						'type'		=> 'text',
						'default'	=> '',
						'label'		=> 'MailChimp API key',
						'place'		=> 'xxxxxxxxxxxxxxxxxxxxxxx-xxx',
						'scope'		=> 'global'
					),
					array(
						'key'		=> 'dms_signup_mailchimp_listid',
						'type'		=> 'text',
						'default'	=> '',
						'label'		=> 'MailChimp List ID',
						'place'		=> 'xxxxxxxx',
						'scope'		=> 'global'
					)
				)
			)
		);
	return $opts;
	}

	function section_template() {
		$send_btn_text = $this->opt( 'dms_signup_submit', array( 'default' => 'Submit <i class="icon icon-chevron-sign-right"></i>' ) );
		$subscribe_btn_text = $this->opt( 'dms_signup_subscribe', array( 'default' => 'Subscribe' ) );
		$subscribe_btn_theme = $this->opt( 'dms_submit_btn_theme', array( 'default' => 'btn-primary') );
		?>
		<div class="dms-signup dms-signup-light">
			<div class="drag-slider pl-animation-group" style="display: none;">				
				<div class="drag-well pl-animation pla-from-bottom"><h2><strong id="slider" class="the-slider"><i class="icon icon-long-arrow-right"></i></strong> <?php echo $subscribe_btn_text; ?></h2></div>
			</div>
			<div class="reply-text" style="display: none;">
				<div class="saving"></div>
				<div class="first-steps">
					<span class="pl-signup-form the-inputs pl-animation-group" action="#">
						<input type="email" class="pl-animation pla-from-bottom get-pl-email the-email" value="" placeholder="Email Address" />
						<span class="btn <?php echo $subscribe_btn_theme;?> btn-large get-pagelines pl-send-email pl-animation pla-from-bottom">
							<span class="get-pl-text" data-sync="get_pagelines_btn_text"><?php echo $send_btn_text; ?></span>
						</span>
					</span>
				</div>
			</div>
		</div>
		<?php
	}

	function get_emails() {
		if( isset( $_GET['dms_signup_emails'] ) && current_user_can( 'edit_theme_options' ) ) {
			$out = '';
			$emails = get_option( 'dms_signup_emails', array() );
			$emails = array_unique( $emails );
			if( empty( $emails ) ) {
				$out = '<h3>No emails yet :/</h3>';
			} else {
				$out = sprintf( '<h3>Emails collected so far: %s</h3>%s', count( $emails ), implode( ",\n", $emails ) );
			}
			wp_die( $out, 'DMS-Signup', array( 'response' => 200 ) );
		}
 	}

	function subscribe() {

		$emails = get_option( 'dms_signup_emails', array() );
		if( empty( $emails ) || ! $emails )
			$emails = array();

		$postdata = $_POST;
		$response = array();
		$responce['error'] = false;
		$response['post'] = $postdata;
		$email = $postdata['email'];
		array_push( $emails, $email );
		update_option( 'dms_signup_emails', $emails );
		$response['email'] = $email;

		include_once( 'lib.mailchimp.php' );

		if( pl_setting( 'dms_signup_mailchimp_api' ) && pl_setting( 'dms_signup_mailchimp_listid' ) ) {
			$MailChimp = new PLMailChimp( pl_setting( 'dms_signup_mailchimp_api' ) );
			$send = $MailChimp->call('lists/subscribe', array(
				'id'				=> pl_setting( 'dms_signup_mailchimp_listid' ),
				'email'				=> array('email'=>$email),
				'merge_vars'		=> array('FNAME'=>'', 'LNAME'=>''),
				'double_optin'		=> false,
				'update_existing'	=> true,
				'replace_interests'	=> false,
				'send_welcome'		=> true,
				)
			);
			$response['mailchimp'] = $send;
		}
		echo json_encode(  pl_arrays_to_objects( $response ) );
		exit(0);
	}
} // class