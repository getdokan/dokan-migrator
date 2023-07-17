<?php
/**
 * Activate dokan vendor dashboard notice.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="notice dokan-admin-notices-wrap dokan-migrator-active-v-dash-notice">
    <div class="dokan-admin-notice dokan-alert">
        <div class="notice-content">
            <div class="logo-wrap">
                <div class="dokan-logo"></div>
                <span class="dokan-icon dokan-icon-alert"></span>
            </div>
            <div class="dokan-message">
                <h3><?php esc_html_e( 'Activate dokan vendor dashboard.', 'dokan-migrator' ); ?></h3>
                <div>
                    <?php
                        /* translators: 1$s: opening anchor tag, 2$s: closing anchor tag */
                        printf( esc_html__( '%1$sActive now%2$s', 'dokan-migrator' ), '<a id="dokan-migrator-active-v-dash" href="#">', '</a>' );
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    ;(function($){
        $( '#dokan-migrator-active-v-dash' ).on( 'click', ( e ) => {
            e.preventDefault();

            $.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', { nonce: '<?php echo wp_create_nonce( 'dokan_migrator_nonce' ); ?>', action: "dokan_migrator_active_vendor_dashboard"}, function(data) {
                if (data.success) {
                    $('.dokan-migrator-active-v-dash-notice').remove();
                }
            });
        } );
    })(jQuery);
</script>
