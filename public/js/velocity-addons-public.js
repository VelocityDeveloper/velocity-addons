(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

})( jQuery );

(function( $ ) {
	'use strict';

	$(function(){
		var $btn = $('.scroll-to-top.floating-button');
		var $footer = $('.floating-footer');
		var $waButton = $('.whatsapp-floating.floating-button, .wa-multi-toggle.floating-button');
		if ($btn.length) {
			var showThreshold = 200;
			var syncWaShift = function(isVisible) {
				if (!$footer.length || !$waButton.length) return;
				var waPos = $footer.data('wa-position');
				var scrollPos = $footer.data('scrolltop-position');
				var sameSide = waPos && scrollPos && waPos === scrollPos;
				$waButton.toggleClass('wa-shift-scrolltop', sameSide && isVisible);
			};
			var onScroll = function() {
				var y = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
				var shouldShow = y > showThreshold;
				if (shouldShow) {
					if (!$btn.is(':visible')) { $btn.stop(true, true).fadeIn(150); }
				} else {
					if ($btn.is(':visible')) { $btn.stop(true, true).fadeOut(150); }
				}
				syncWaShift(shouldShow);
			};
			onScroll();
			$(window).on('scroll.vdScrollTop', onScroll);
			$btn.on('click', function(e){
				e.preventDefault();
				$('html, body').animate({ scrollTop: 0 }, 300);
			});
		}

		// Floating WhatsApp multi-contact toggle
		var $waToggle = $('.wa-multi-toggle');
		var $waList = $('#wa-multi-list');
		if ($waToggle.length && $waList.length) {
			var closeList = function() {
				$waList.removeClass('is-open');
				$waToggle.attr('aria-expanded', 'false').removeClass('is-open');
			};
			var openList = function() {
				$waList.addClass('is-open');
				$waToggle.attr('aria-expanded', 'true').addClass('is-open');
			};
			$waToggle.on('click', function(e) {
				e.preventDefault();
				var isOpen = $waList.hasClass('is-open');
				var isCloseIcon = $(e.target).closest('.wa-toggle-close').length > 0;

				if (!isOpen) {
					openList();
					return;
				}

				// Only close when clicking the close icon.
				if (isOpen && isCloseIcon) {
					closeList();
				}
			});
		}
	});

})( jQuery );
