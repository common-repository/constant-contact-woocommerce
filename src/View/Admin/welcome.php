<?php
    $url = admin_url( 'admin.php?page=' . esc_attr( $_GET['page'] ) );
    $url = add_query_arg( array(
        'cc-connect' => 'connect',
        'tab'        => 'wc-settings' === $_GET['page'] ? 'cc_woo' : '',
    ), $url );


?>
<div class="cc-woo-welcome-wrap">
    <div class="container">
        <img alt="<?php esc_attr_e( 'Constant Contact logo', 'constant-contact-woocommerce' ); ?>" class="cc-logo-main" src="<?php echo plugin_dir_url( __FILE__ ) . '../../assets/ctct.png'?>" />
        <h1>
            <?php esc_html_e( 'Constant Contact for WooCommerce', 'constant-contact-woocommerce' ); ?>
        </h1>
        <p>
        <?php
            echo wp_kses_post (
                sprintf(
                __(
                    'Looks like you have not connected your account yet, You will first need to enter the information required in order to connect your account. If you have any issues connecting please call %sConstant Contact Support%s',
                    'constant-contact-woocommerce'
                ),
                '<a href="' . esc_url( "https://community.constantcontact.com/contact-support" ) . '">',
                '</a>'
                )
            );
        ?>
        </p>
        <a href="<?php echo esc_url( $url ); ?>" class="cc-woo-btn btn-primary"> <?php esc_html_e( "Let's Start", 'constant-contact-woocommerce' ); ?> </a>
    </div>
</div>
