!function ($) {
	function plIsEmail(email) {
	  var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	  return regex.test(email);
	}

	jQuery(document).ready(function() {

		$('.dms-signup').each(function(){
			var signUp 		= $(this)
			,	well 		= signUp.find('.drag-well')
			,	sldr 		= signUp.find('.the-slider')
			,	fullSldr 	= $('.drag-slider')
			,	pix			= 175

			sldr.draggable({
				axis: 'x',
				containment: 'parent',
				drag: function(event, ui) {

					if (ui.position.left > pix) {
						well.css('opacity', '.8')

					} else {
						well.css('opacity', '1')
					}
				},
				stop: function(event, ui) {
					if (ui.position.left < pix) {
						$(this).animate({
							left: 0
						})
					} else {
						fullSldr.slideUp()
						$(this).closest('.dms-signup').find('.reply-text').slideDown()
					}
				}
			})

			// The following credit: http://www.evanblack.com/blog/touch-slide-to-unlock/

			sldr[0].addEventListener('touchmove', function(e) {
			    e.preventDefault()

			    var el = e.target
				,	elem = $(el)
			    ,	touch = e.touches[0]

				if(elem.hasClass('icon'))
					el = elem.parent()[0]

			    curX = touch.pageX - this.offsetLeft - (pix / 2);
			    if(curX <= 0) return;
			    if(curX > pix){
			    	fullSldr.slideUp()
					$(this).closest('.dms-signup').find('.reply-text').slideDown()
			    }
			   	el.style.webkitTransform = 'translateX(' + curX + 'px)';
			}, false);

			sldr[0].addEventListener('touchend', function(event) {
			    this.style.webkitTransition = '-webkit-transform 0.3s ease-in';
			    this.addEventListener( 'webkitTransitionEnd', function( event ) { this.style.webkitTransition = 'none'; }, false );
			    this.style.webkitTransform = 'translateX(0px)';
			}, false);

		})

			$(".get-pl-email").keypress(function(event) {
			    if (event.which == 13) {
			        event.preventDefault();
			        $('.pl-send-email').trigger('click')
			    }
			});

			$('.pl-send-email').on('click', function(){

					var theButton = $(this)
					,	email = $('.get-pl-email.the-email').val() || false

					if( ! email || ! plIsEmail(email) ){
						var msg

						if( ! email ){

							msg = '<i class="icon icon-warning"></i> Please enter an email.'

						} else if ( ! plIsEmail( email ) ){
							msg = '<i class="icon icon-warning"></i> Please enter valid email.'
						}

						$('.reply-text .saving')
							.html( msg )
							.slideDown()

						setTimeout(function(){
							$('.reply-text .saving').slideUp()
						} , 2500)

						return;
					}

					$.ajax({
						type: 'POST'
					  	, url: window.dmssignupajax.url
						, data: {
							action: 'pl_ajax_dms_subscribe'
							, email: email
						}
						, beforeSend: function(){
							$('.reply-text .saving')
								.html(window.dmssignupajax.sending).slideDown()

						}
						, error: function( response ){
              $('.first-steps').hide()
							$('.reply-text .saving').html('AJAX Error :/')
						}
						, success: function(response){

							console.log( response )
							var rsp	= $.parseJSON( response )
							,  email = rsp.email || ''
              ,  error = rsp.error || false
							,  responseContainer = $('.reply-text .saving')
              ,  response = ''

              if( ! error ) {
                response = window.dmssignupajax.replytxt.replace('[email]',email)
              } else {
                reponse = error
              }

							$('.first-steps').hide()
              responseContainer.html( response )
						}
					})

			})



	})
}(window.jQuery);
