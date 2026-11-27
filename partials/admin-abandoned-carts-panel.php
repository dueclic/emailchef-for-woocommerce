<?php
// Abandoned carts sent to Emailchef: summary, last five and history dialog (filled by emailchef.js).
?>
    <section class="emailchef-debug__card emailchef-debug__abcart" id="emailchef-abcart-panel" aria-labelledby="emailchef-abcart-title">
        <div class="emailchef-debug__abcart-intro">
            <h2 id="emailchef-abcart-title"><?php esc_html_e( 'Abandoned carts', 'emailchef-for-woocommerce' ); ?></h2>
            <p id="emailchef-abcart-delay"></p>
        </div>
        <dl class="emailchef-debug__abcart-stats" id="emailchef-abcart-stats" aria-busy="true">
            <div><dt><?php esc_html_e( 'Waiting', 'emailchef-for-woocommerce' ); ?></dt><dd id="emailchef-abcart-pending">–</dd></div>
            <div><dt><?php esc_html_e( 'Sent (7 days)', 'emailchef-for-woocommerce' ); ?></dt><dd id="emailchef-abcart-synced">–</dd></div>
            <div><dt><?php esc_html_e( 'Errors (7 days)', 'emailchef-for-woocommerce' ); ?></dt><dd id="emailchef-abcart-errors">–</dd></div>
        </dl>
        <p class="emailchef-debug__abcart-next" id="emailchef-abcart-next"></p>
        <h3 class="emailchef-debug__abcart-subtitle" id="emailchef-abcart-recent-title"><?php esc_html_e( 'Last sent', 'emailchef-for-woocommerce' ); ?></h3>
        <ul class="emailchef-debug__abcart-list" id="emailchef-abcart-recent" aria-labelledby="emailchef-abcart-recent-title"></ul>
        <p class="emailchef-debug__abcart-empty" id="emailchef-abcart-empty" role="status"></p>
        <button type="button" class="button emailchef-debug__results-button" id="emailchef-abcart-open-history" aria-haspopup="dialog" hidden><?php esc_html_e( 'View history', 'emailchef-for-woocommerce' ); ?></button>
        <p class="emailchef-debug__abcart-retention" id="emailchef-abcart-retention"></p>
    </section>
    <dialog id="emailchef-abcart-dialog" class="emailchef-debug__dialog" aria-labelledby="emailchef-abcart-dialog-title" aria-describedby="emailchef-abcart-dialog-desc">
        <div class="emailchef-debug__dialog-header">
            <div>
                <h2 id="emailchef-abcart-dialog-title"><?php esc_html_e( 'Abandoned carts history', 'emailchef-for-woocommerce' ); ?></h2>
                <p id="emailchef-abcart-dialog-desc"><?php esc_html_e( 'Each row shows the data sent to Emailchef, which the abandoned cart email uses.', 'emailchef-for-woocommerce' ); ?></p>
            </div>
            <button type="button" class="emailchef-debug__dialog-close" id="emailchef-abcart-close-history" aria-label="<?php esc_attr_e( 'Close', 'emailchef-for-woocommerce' ); ?>">&times;</button>
        </div>
        <div class="emailchef-debug__abcart-toolbar">
            <label for="emailchef-abcart-filter"><?php esc_html_e( 'Result', 'emailchef-for-woocommerce' ); ?></label>
            <select id="emailchef-abcart-filter">
                <option value=""><?php esc_html_e( 'All', 'emailchef-for-woocommerce' ); ?></option>
                <option value="synced"><?php esc_html_e( 'Sent', 'emailchef-for-woocommerce' ); ?></option>
                <option value="error"><?php esc_html_e( 'Error', 'emailchef-for-woocommerce' ); ?></option>
                <option value="legacy"><?php esc_html_e( 'Previous history', 'emailchef-for-woocommerce' ); ?></option>
            </select>
        </div>
        <div class="emailchef-debug__dialog-table"><table class="widefat emailchef-debug__abcart-table" id="emailchef-abcart-history-table">
            <caption class="screen-reader-text"><?php esc_html_e( 'Abandoned carts history', 'emailchef-for-woocommerce' ); ?></caption>
            <thead><tr><th scope="col"><?php esc_html_e( 'Time', 'emailchef-for-woocommerce' ); ?></th><th scope="col"><?php esc_html_e( 'Customer', 'emailchef-for-woocommerce' ); ?></th><th scope="col"><?php esc_html_e( 'Product', 'emailchef-for-woocommerce' ); ?></th><th scope="col"><?php esc_html_e( 'Result', 'emailchef-for-woocommerce' ); ?></th><th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Details', 'emailchef-for-woocommerce' ); ?></span></th></tr></thead>
            <tbody id="emailchef-abcart-history"></tbody>
        </table></div>
        <p id="emailchef-abcart-history-empty" role="status"></p>
        <nav class="emailchef-debug__pagination" aria-label="<?php esc_attr_e( 'Abandoned carts history pages', 'emailchef-for-woocommerce' ); ?>">
            <span id="emailchef-abcart-page" aria-live="polite"></span>
            <div>
                <button type="button" class="button" id="emailchef-abcart-prev" aria-label="<?php esc_attr_e( 'Previous page', 'emailchef-for-woocommerce' ); ?>">&lsaquo;</button>
                <button type="button" class="button" id="emailchef-abcart-next-page" aria-label="<?php esc_attr_e( 'Next page', 'emailchef-for-woocommerce' ); ?>">&rsaquo;</button>
            </div>
        </nav>
    </dialog>
