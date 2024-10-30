
/**
 * GuestCheckoutCapture.
 *
 * @package WebDevStudios\CCForWoo
 * @since   1.2.0
 */
export default class HandleSettingsPage {

    /**
     * @constructor
     *
     * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
     * @since 2.0.0
     */
    constructor() {
        this.els = {};
    }

    /**
     * Init ccWoo admin JS.
     *
     * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
     * @since 2.0.0
     */
    init() {
        this.cacheEls();
        this.bindEvents();
        this.enableStoreDetails();
    }

    /**
     * Cache some DOM elements.
     *
     * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
     * @since 2.0.0
     */
    cacheEls() {
        this.els.enableStoreDetails = document.getElementById( 'cc_woo_save_store_details' );
        this.els.optionalFields     = document.getElementById( 'cc-optional-fields' );
    }

    /**
     * Bind callbacks to events.
     *
     * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
     * @since 2.0.0
     */
    bindEvents() {
        if ( null !== this.els.enableStoreDetails ) {
            this.els.enableStoreDetails.addEventListener('change', e => {
                this.enableStoreDetails();
            });
        }
    }

    /**
     * Captures guest checkout if billing email is valid.
     *
     * @author Biplav Subedi <biplav.subedi@webdevstudios.com>
     * @since 2.0.0
     */
     enableStoreDetails() {
        if (null !== this.els.enableStoreDetails) {
            if (this.els.enableStoreDetails.checked) {
                console.log(this.els.optionalFields.parentElement);
                this.els.optionalFields.parentElement.style.display = 'block';
            } else {
                this.els.optionalFields.parentElement.style.display = 'none';
            }
        }
    }

}
