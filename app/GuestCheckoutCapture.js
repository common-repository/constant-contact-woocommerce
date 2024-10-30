import validateEmail from 'filter-validate-email';

/**
 * GuestCheckoutCapture.
 *
 * @package WebDevStudios\CCForWoo
 * @since   1.2.0
 */
export default class GuestCheckoutCapture {

    /**
     * @constructor
     *
     * @author George Gecewicz <george.gecewicz@webdevstudios.com>
     * @since  1.2.0
     */
    constructor() {
        this.els = {};
    }

    /**
     * Init ccWoo public JS.
     *
     * @author George Gecewicz <george.gecewicz@webdevstudios.com>
     * @since  1.2.0
     */
    init() {
        this.cacheEls();
        this.bindEvents();
    }

    /**
     * Cache some DOM elements.
     *
     * @author George Gecewicz <george.gecewicz@webdevstudios.com>
     * @since 1.2.0
     */
    cacheEls() {
        this.els.billingEmail = document.getElementById( 'billing_email' );
        this.els.wcCheckoutNonce = document.getElementById( 'woocommerce-process-checkout-nonce' );
    }

    /**
     * Bind callbacks to events.
     *
     * @author George Gecewicz <george.gecewicz@webdevstudios.com>
     * @since 1.2.0
     */
    bindEvents() {
        this.els.billingEmail.addEventListener( 'focusout', e => {
            if ( validateEmail( e.target.value ) ) {
                this.maybeCaptureGuestCheckout( e.target.value );
            }
        } );
    }

    /**
     * Captures guest checkout if billing email is valid.
     *
     * @author George Gecewicz <george.gecewicz@webdevstudios.com>
     * @since 1.2.0
     *
     * @param {string} emailAddr Billing email address entered by user.
     */
    maybeCaptureGuestCheckout( emailAddr ) {
        wp.ajax.send( 'cc_woo_abandoned_checkouts_capture_guest_checkout', {
            data: {
                nonce: this.els.wcCheckoutNonce.value,
                email: emailAddr
            }
        } );
    }

}
