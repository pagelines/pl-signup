<?php
/*
Plugin Name: PageLines Signup
Version: 0.1
PageLines: true
Section: true
Class Name: pl_signup
*/

// early bail if we're not in PageLines.
if( ! class_exists( 'PageLinesSection' ) )
  return;

class pl_signup extends PageLinesSection{

  function section_opts() {
    $opts = array(
      array(
        'type'	=> 'multi',
        'title'  => 'PageLines Email Subscribe Settings',
        'help'    => sprintf( 'All emails will be saved to a WordPress option (pl_signup_emails)<br />If Mailchimp is enabled they will be sent there too.<br />To view the emails in a browser (CSV) click <a href="%s">HERE</a>', site_url( '?pl_signup_emails=1' ) ),
        'opts'  => array(
          array(
            'key'  => 'pl_signup_submit',
            'type'  => 'text',
            'default'  => 'Submit <i class="icon icon-chevron-sign-right"></i>',
            'label'  => 'Submit button text',
          ),
          array(
            'key'  => 'pl_signup_subscribe',
            'type'  => 'text',
            'default'  => 'Subscribe',
            'label'  => 'Subscribe button text',
          ),
          array(
            'key'  => 'pl_signup_sending_txt',
            'type'  => 'text',
            'label'  => 'Text to show during send',
            'place'    => "<i class='icon icon-refresh icon-spin spin-fast'></i> Sending...",
            'scope'    => 'global'
          ),
          array(
            'key'      => 'pl_signup_success_txt',
            'type'     => 'text',
            'place'    => "[email] was subscribed <i class='icon icon-smile'></i>",
            'label'    => 'Text to show on success',
            'scope'    => 'global'
          ),
          array(
            'key'      => 'pl_submit_btn_theme',
            'type'     => 'select_button',
            'label'    => __( 'Button Color', 'pagelines'),
          ),
        )
      )
    );

    return $opts;
  }

  function section_template() {
    $send_btn_text = $this->opt( 'pl_signup_submit', array( 'default' => 'Submit <i class="icon icon-chevron-sign-right"></i>' ) );
    $subscribe_btn_text = $this->opt( 'pl_signup_subscribe', array( 'default' => 'Subscribe' ) );
		$subscribe_btn_theme = $this->opt( 'pl_submit_btn_theme', array( 'default' => 'btn-primary') );
    ?>

		<div class="pl-signup pl-signup-light">
			<div class="drag-slider">
				<div class="drag-well"><h2><strong id="slider" class="the-slider"><i class="icon icon-long-arrow-right"></i></strong> <span class="txt"><?php echo $subscribe_btn_text; ?></span></h2></div>
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

  function section_styles(){

		wp_enqueue_script( 'jquery-ui-draggable' );

		wp_enqueue_script( 'pl-signup', $this->base_url.'/scripts.js', array( 'jquery' ), pl_get_cache_key(), false );

		$ajax_url = admin_url( 'admin-ajax.php' );
		if ( has_action( 'pl_force_ssl' ) )
			$ajax_url = str_replace( 'http://', 'https://', $ajax_url );
		wp_localize_script( 'pl-signup', 'plsignupajax', array(
            'url'         => $ajax_url,
            'replytxt'    => pl_setting( 'pl_signup_success_txt', array( 'default' => "[email] was subscribed <i class='icon icon-smile'></i>" ) ),
            'sending'     => pl_setting( 'pl_signup_sending_txt', array( 'default' => "<i class='icon icon-refresh icon-spin spin-fast'></i> Sending..." ) )
        ) );
	}

  function section_persistent() {
    add_action( 'wp_ajax_nopriv_pl_ajax_pl_subscribe'    , array( $this, 'subscribe' ) );
    add_action( 'wp_ajax_pl_ajax_pl_subscribe'           , array( $this, 'subscribe' ) );
    add_action( 'init'                                    , array( $this, 'get_emails' ) );
  }

  function get_emails() {
    if( isset( $_GET['pl_signup_emails'] ) && current_user_can( 'edit_theme_options' ) ) {
        $out = '';
        $emails = get_option( 'pl_signup_emails', array() );
        if( empty( $emails ) ) {
            $out = '<h3>No emails yet :/</h3>';
        } else {
            $out = implode( ',', array_unique( $emails ) );
        }
        wp_die( $out );


    }
  }

  function subscribe() {

      $emails = get_option( 'pl_signup_emails', array() );
      if( empty( $emails ) || ! $emails )
        $emails = array();

    	$postdata = $_POST;
      $response = array();
      $responce['error'] = false;
      $response['post'] = $postdata;

      $email = $postdata['email'];
      array_push( $emails, $email );
      update_option( 'pl_signup_emails', $emails );

      $response['email'] = $email;

      echo json_encode(  pl_arrays_to_objects( $response ) );
      exit(0);
  }


} // class
