<?php
/**
 * Migrate to dokan admin notice.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<div class="notice dokan-admin-notices-wrap migrate-to-dokan dokan-migrator-active-v-dash-notice">
    <div class="dokan-admin-notice dokan-alert migrate-to-dokan">
        <div class="notice-content">
            <div class="logo-wrap">
                <div class="dokan-logo"></div>
                <span class="dokan-icon dokan-icon-alert"></span>
            </div>
            <div class="dokan-message">
                <h3>
                <?php
                    /* translators: 1$s: the plugin to migrate to dokan */
                    printf( esc_html__( 'Do You Want To %1$s ?', 'dokan-migrator' ), $data['set_title'] );
                ?>
                </h3>
                <div>
                <?php
                    /* translators: 1$s: opening anchor tag, 2$s: closing anchor tag */
                    printf( esc_html__( 'Click %1$1sHere%2$2s to move to the migration process.', 'dokan-migrator' ), '<a href="' . menu_page_url( 'dokan-migrator', false ) . '">', '</a>' );
                ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.migrate-to-dokan{
    background: linear-gradient(144deg, rgba(255,255,255,1) 30%, rgba(241,105,130,1) 100%) !important;
    border-radius: 5px;
    box-shadow: rgb(0 0 0 / 16%) 0px 1px 4px;
}
</style>

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
