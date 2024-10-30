/**
 * HandleAdminNotifDismiss.
 *
 * @package WebDevStudios\CCForWoo
 * @since   NEXT
 */
export default class HandleAdminNotifDismiss {

	/**
	 * @constructor
	 *
	 * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
	 * @since NEXT
	 */
	constructor() {
		this.els = {};
	}

	/**
	 * Init ccWoo admin JS.
	 *
	 * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
	 * @since NEXT
	 */
	init() {
		this.cacheEls();
		this.bindEvents();
	}

	/**
	 * Cache some DOM elements.
	 *
	 * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
	 * @since NEXT
	 */
	cacheEls() {
		this.els.dismissNotification = document.querySelector('#cc-woo-review-dismiss');
	}

	/**
	 * Bind callbacks to events.
	 *
	 * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
	 * @since NEXT
	 */
	bindEvents() {
		if (null !== this.els.dismissNotification) {
			const nonce = this.els.dismissNotification.dataset.nonce;
			const dismissbtn = this.els.dismissNotification.querySelector('#cc-woo-review-dismiss');

			if (dismissbtn) {
				dismissbtn.addEventListener('click', e => {
					this.handleDismiss(nonce);
					e.preventDefault();
					this.els.dismissNotification.style.display = 'none';
				});
			}

			const alreadyReviewed = this.els.dismissNotification.querySelector('#already-reviewed');
			if (alreadyReviewed) {
				alreadyReviewed.addEventListener('click', e => {
					this.handleAlreadyReviewed(nonce);
					e.preventDefault();
					this.els.dismissNotification.style.display = 'none';
				});
			}
		}
	}

	/**
	 * Handle admin notice dismissal
	 * @param nonce
	 * @since NEXT
	 */
	handleDismiss( nonce ) {
		const url = cc_woo_ajax.ajax_url;
		const cc_woo_args = new URLSearchParams({
			action      : 'cc_woo_increment_dismissed_count',
			cc_woo_nonce: nonce,
		}).toString();

		const request = new XMLHttpRequest();

		request.open('POST', url, true);
		request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
		request.onload = function () {
			if (this.status >= 200 && this.status < 400) {
				console.log(this.response);
			} else {
				console.log(this.response);
			}
		};
		request.onerror = function () {
			console.log('update failed');
		};
		request.send(cc_woo_args);
	}

	/**
	 * Handle admin notice already reviewed dismissal
	 * @param nonce
	 * @since NEXT
	 */
	handleAlreadyReviewed( nonce ) {
		const url = cc_woo_ajax.ajax_url;
		const cc_woo_args = new URLSearchParams({
			action      : 'cc_woo_set_already_reviewed',
			cc_woo_nonce: nonce,
		}).toString();

		const request = new XMLHttpRequest();

		request.open('POST', url, true);
		request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
		request.onload = function () {
			if (this.status >= 200 && this.status < 400) {
				console.log(this.response);
			} else {
				console.log(this.response);
			}
		};
		request.onerror = function () {
			console.log('update failed');
		};
		request.send(cc_woo_args);
	}

}
