<?php
// Shared synchronization controls for settings and diagnostics.
?>
    <section class="emailchef-debug__card emailchef-debug__sync" data-list-available="<?php echo empty( $list_id ) ? '0' : '1'; ?>">
        <div class="emailchef-debug__sync-intro">
            <span class="emailchef-debug__eyebrow"><?php esc_html_e( 'CUSTOMERS', 'emailchef-for-woocommerce' ); ?></span>
            <h2><?php esc_html_e( 'Initial synchronization', 'emailchef-for-woocommerce' ); ?></h2>
            <p><?php esc_html_e( 'Synchronize existing customers and custom fields in manageable batches.', 'emailchef-for-woocommerce' ); ?></p>
            <div class="emailchef-debug__batch-setting">
                <label for="emailchef-batch-size"><strong><?php esc_html_e( 'Contacts per batch', 'emailchef-for-woocommerce' ); ?></strong> <output id="emailchef-batch-size-value" for="emailchef-batch-size"><?php echo esc_html( WC_Emailchef_Initial_Sync::batch_size() ); ?></output></label>
                <input type="range" id="emailchef-batch-size" min="5" max="100" step="5" value="<?php echo esc_attr( WC_Emailchef_Initial_Sync::batch_size() ); ?>">
                <p><?php echo esc_html( sprintf( __( 'Suggested: %d contacts, based on configured memory limits. Changes apply from the next batch; actual memory use varies.', 'emailchef-for-woocommerce' ), WC_Emailchef_Initial_Sync::suggested_batch_size() ) ); ?></p>
                <p id="emailchef-batch-save-status" role="status"></p>
            </div>
            <button type="button" class="button button-primary" id="emailchef-button-initial-sync" <?php disabled( empty( $list_id ) ); ?>><?php esc_html_e( 'Schedule initial synchronization', 'emailchef-for-woocommerce' ); ?></button>
        </div>
        <div id="emailchef-sync-status" class="emailchef-debug__sync-status" data-status="idle" aria-live="polite">
            <div class="emailchef-debug__status-heading"><span class="emailchef-debug__status-dot" aria-hidden="true"></span><strong id="emailchef-sync-message"><?php esc_html_e( 'Loading synchronization status...', 'emailchef-for-woocommerce' ); ?></strong></div>
            <progress id="emailchef-sync-progress" max="100" value="0" aria-label="<?php esc_attr_e( 'Synchronization progress', 'emailchef-for-woocommerce' ); ?>"></progress>
            <p id="emailchef-sync-details"></p>
            <button type="button" class="button emailchef-debug__results-button" id="emailchef-open-sync-results"><?php esc_html_e( 'View batch results', 'emailchef-for-woocommerce' ); ?></button>
        </div>
    </section>
    <dialog id="emailchef-sync-dialog" class="emailchef-debug__dialog" aria-labelledby="emailchef-sync-dialog-title">
        <div class="emailchef-debug__dialog-header">
            <div><span class="emailchef-debug__eyebrow"><?php esc_html_e( 'INITIAL SYNCHRONIZATION', 'emailchef-for-woocommerce' ); ?></span>
            <h2 id="emailchef-sync-dialog-title"><?php esc_html_e( 'Batch results', 'emailchef-for-woocommerce' ); ?></h2>
            <p><?php esc_html_e( 'Results update automatically. Accepted means Emailchef queued the batch for processing.', 'emailchef-for-woocommerce' ); ?></p></div>
            <button type="button" class="emailchef-debug__dialog-close" id="emailchef-close-sync-results" aria-label="<?php esc_attr_e( 'Close', 'emailchef-for-woocommerce' ); ?>">&times;</button>
        </div>
        <p id="emailchef-sync-dialog-status" class="emailchef-debug__dialog-status"></p>
        <div class="emailchef-debug__dialog-table"><table class="widefat striped">
            <thead><tr><th><?php esc_html_e( 'Batch', 'emailchef-for-woocommerce' ); ?></th><th><?php esc_html_e( 'Time', 'emailchef-for-woocommerce' ); ?></th><th><?php esc_html_e( 'Result', 'emailchef-for-woocommerce' ); ?></th><th><?php esc_html_e( 'Accepted / sent', 'emailchef-for-woocommerce' ); ?></th><th><?php esc_html_e( 'Details', 'emailchef-for-woocommerce' ); ?></th></tr></thead>
            <tbody id="emailchef-sync-chunks"></tbody>
        </table></div>
        <p id="emailchef-sync-chunks-empty"><?php esc_html_e( 'No batch results recorded yet. Results are recorded from this update onward.', 'emailchef-for-woocommerce' ); ?></p>
    </dialog>
