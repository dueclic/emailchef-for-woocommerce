<?php
$wcec = WCEC();
$list_id = wc_ec_get_option_value( 'list' );
$api = $wcec->emailchef();
?>
<div class="wrap emailchef-debug">
    <header class="emailchef-debug__header">
        <div>
            <img class="emailchef-debug__logo" src="<?php echo esc_url( plugins_url( '/dist/img/logo-compact.svg', WC_EMAILCHEF_FILE ) ); ?>" alt="Emailchef">
            <h1><?php esc_html_e( 'Debug dashboard', 'emailchef-for-woocommerce' ); ?></h1>
            <p><?php esc_html_e( 'Monitor synchronization and inspect your store connection.', 'emailchef-for-woocommerce' ); ?></p>
        </div>
        <span class="emailchef-debug__badge"><?php esc_html_e( 'Diagnostics', 'emailchef-for-woocommerce' ); ?></span>
    </header>

    <div class="emailchef-debug__overview">
        <section class="emailchef-debug__card emailchef-debug__connection">
            <div class="emailchef-debug__section-heading">
                <div><span class="emailchef-debug__eyebrow"><?php esc_html_e( 'OVERVIEW', 'emailchef-for-woocommerce' ); ?></span>
                <h2><?php esc_html_e( 'Connection', 'emailchef-for-woocommerce' ); ?></h2></div>
                <span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
            </div>
            <dl class="emailchef-debug__facts">
                <div><dt><?php esc_html_e( 'API URL', 'emailchef-for-woocommerce' ); ?></dt><dd><?php echo esc_html( $wcec->get_api_url() ); ?></dd></div>
                <div><dt><?php esc_html_e( 'List ID', 'emailchef-for-woocommerce' ); ?></dt><dd><?php echo esc_html( $list_id ?: __( 'Not found', 'emailchef-for-woocommerce' ) ); ?></dd></div>
                <?php if ( $api ) : ?><div><dt><?php esc_html_e( 'Policy', 'emailchef-for-woocommerce' ); ?></dt><dd><?php echo esc_html( $api->get_policy() ); ?></dd></div><?php endif; ?>
            </dl>
        </section>
        <section class="emailchef-debug__card emailchef-debug__memory">
            <div class="emailchef-debug__section-heading">
                <div><span class="emailchef-debug__eyebrow"><?php esc_html_e( 'SERVER', 'emailchef-for-woocommerce' ); ?></span>
                <h2><?php esc_html_e( 'Memory limits', 'emailchef-for-woocommerce' ); ?></h2></div>
                <span class="dashicons dashicons-performance" aria-hidden="true"></span>
            </div>
            <dl class="emailchef-debug__facts">
                <div><dt><?php esc_html_e( 'PHP', 'emailchef-for-woocommerce' ); ?></dt><dd><?php echo esc_html( ini_get( 'memory_limit' ) ); ?></dd></div>
                <div><dt><?php esc_html_e( 'WordPress', 'emailchef-for-woocommerce' ); ?></dt><dd><?php echo esc_html( WP_MEMORY_LIMIT ); ?></dd></div>
                <div><dt><?php esc_html_e( 'WordPress maximum', 'emailchef-for-woocommerce' ); ?></dt><dd><?php echo esc_html( WP_MAX_MEMORY_LIMIT ); ?></dd></div>
            </dl>
        </section>
    </div>

    <?php if ( $api && ! empty( $list_id ) ) : ?>
    <?php require WC_EMAILCHEF_DIR . 'partials/admin-sync-panel.php'; ?>
    <div class="emailchef-debug__tools">
        <section class="emailchef-debug__card">
            <span class="emailchef-debug__eyebrow"><?php esc_html_e( 'SCHEMA', 'emailchef-for-woocommerce' ); ?></span>
            <h2><?php esc_html_e( 'Custom fields', 'emailchef-for-woocommerce' ); ?></h2>
            <p><?php esc_html_e( 'Rebuild the fields used by the integration without synchronizing customers.', 'emailchef-for-woocommerce' ); ?></p>
            <button class="button" id="emailchef-button-rebuild-customfields"><?php esc_html_e( 'Rebuild custom fields', 'emailchef-for-woocommerce' ); ?></button>
            <details class="emailchef-debug__details"><summary><?php esc_html_e( 'View custom fields data', 'emailchef-for-woocommerce' ); ?></summary>
                <textarea rows="8" class="large-text" readonly aria-label="<?php esc_attr_e( 'Custom fields data', 'emailchef-for-woocommerce' ); ?>"><?php echo esc_textarea( wp_json_encode( $api->get_collection( $list_id ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></textarea>
            </details>
        </section>
        <section class="emailchef-debug__card">
            <span class="emailchef-debug__eyebrow"><?php esc_html_e( 'CONNECTION', 'emailchef-for-woocommerce' ); ?></span>
            <h2><?php esc_html_e( 'Active integrations', 'emailchef-for-woocommerce' ); ?></h2>
            <p><?php esc_html_e( 'Inspect integrations connected to the selected list.', 'emailchef-for-woocommerce' ); ?></p>
            <details class="emailchef-debug__details"><summary><?php esc_html_e( 'View integration data', 'emailchef-for-woocommerce' ); ?></summary>
                <pre><?php echo esc_html( wp_json_encode( $api->get_integrations( $list_id ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
            </details>
        </section>
    </div>
    <?php endif; ?>

    <section id="emailchef-abandoned-carts" class="emailchef-debug__card emailchef-debug__carts">
        <div class="emailchef-debug__section-heading"><div>
            <span class="emailchef-debug__eyebrow"><?php esc_html_e( 'STORE ACTIVITY', 'emailchef-for-woocommerce' ); ?></span>
            <h2><?php esc_html_e( 'Abandoned carts', 'emailchef-for-woocommerce' ); ?></h2>
        </div><span class="emailchef-debug__count"><?php echo esc_html( $carts_total ); ?></span></div>

	<?php
	if ( count( $carts ) > 0 ):
		?>

    <div class="emailchef-debug__cart-actions">

        <button class="button button-primary" id="emailchef-button-move-abandoned-carts">
			<?php _e( "Move abandoned carts", "emailchef-for-woocommerce" ); ?>
        </button>

    </div>

        <div class="emailchef-debug__table-wrap"><table class="wp-list-table emailchef-abcart-table widefat fixed striped pages">
            <thead>
            <tr>
                <th>
					<?php
					_e( "User", "emailchef-for-woocommerce" ); ?>
                </th>
                <th>
					<?php
					_e( "Email", "emailchef-for-woocommerce" ); ?>
                </th>
                <th>
					<?php
					_e( "Product image", "emailchef-for-woocommerce" ); ?>
                </th>
                <th>
					<?php
					_e( "Product name", "emailchef-for-woocommerce" ); ?>
                </th>
                <th>
					<?php
					_e( "Product price", "emailchef-for-woocommerce" ); ?>
                </th>
                <th>
					<?php
					_e( "Created", "emailchef-for-woocommerce" ); ?>
                </th>
                <th>
					<?php
					_e( "Force Sync", "emailchef-for-woocommerce" ); ?>
                </th>
            </tr>
            </thead>
            <tbody>
			<?php
			foreach ( $carts as $cart ):

				$customer = null;

				try {
					$customer = new \WC_Customer( $cart['user_id'] );
				} catch ( Exception $e ) {
					continue;
				}

				$customer_title = $customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name();

				if ( empty( $customer_title ) ) {
					$customer_title = __( 'No name provided', 'emailchef-for-woocommerce' );
				}
				$customer_search_encode = urlencode( $customer_title );

				$product = wc_get_product( $cart['product_id'] );
				if ( ! $product ) {
					continue;
				}
				?>
                <tr>
                    <td>
                        <p>
                            <a target="_blank"
                               href="<?php echo admin_url( "admin.php?page=wc-admin&path=%2Fcustomers&search=" . $customer_search_encode ); ?>">
								<?php
								echo $customer_title;
								?>
                            </a>
                        </p>
                    </td>
                    <td>
                        <a href="mailto:<?php echo $customer->get_email(); ?>">
							<?php
							echo $customer->get_email();
							?>
                        </a>
                    </td>
                    <td class="column-image">
                        <a target="_blank" href="<?php
						echo $product->get_permalink(); ?>">
							<?php
							echo $product->get_image();
							?>
                        </a>
                    </td>
                    <td>
                        <a target="_blank" href="<?php
						echo $product->get_permalink(); ?>">
							<?php
							echo $product->get_name();
							?>
                        </a>
                    </td>
                    <td>
						<?php
						echo $product->get_price_html();
						?>
                    </td>
                    <td>
						<?php
						echo $cart['created'];
						?>
                    </td>
                    <td>
                        <button class="button button-primary emailchef-button-force-sync" data-user-id="<?php
						echo $cart['user_id']; ?>" data-user-email="<?php echo $cart['user_email']; ?>">
							<?php
							_e( "Sync", "emailchef-for-woocommerce" ); ?>
                        </button>
                    </td>
                </tr>
			<?php
			endforeach;
			?>
            </tbody>
        </table></div>
        <?php if ( $carts_pages > 1 ) : ?>
        <nav class="emailchef-debug__pagination" aria-label="<?php esc_attr_e( 'Abandoned carts pages', 'emailchef-for-woocommerce' ); ?>">
            <span><?php echo esc_html( sprintf( __( '%1$d items · Page %2$d of %3$d', 'emailchef-for-woocommerce' ), $carts_total, $carts_page, $carts_pages ) ); ?></span>
            <div><?php
                $pagination_base = add_query_arg( array( 'page' => 'emailchef-debug', 'carts_page' => '%#%' ), admin_url( 'admin.php' ) );
                echo wp_kses_post( paginate_links( array(
                    'base' => $pagination_base . '#emailchef-abandoned-carts',
                    'format' => '', 'current' => $carts_page, 'total' => $carts_pages,
                    'prev_text' => __( 'Previous', 'emailchef-for-woocommerce' ),
                    'next_text' => __( 'Next', 'emailchef-for-woocommerce' ),
                ) ) );
            ?></div>
        </nav>
        <?php endif; ?>
	<?php
	else:
		?>
        <p>
			<?php
			_e( "No abandoned carts found", "emailchef-for-woocommerce" );
			?>
        </p>
	<?php
	endif;
	?>

    </section>
</div>
