<?php
$wcec = WCEC();

$list_id = wc_ec_get_option_value( "list" );

?>

<div class="wrap">

    <h1 class="wp-heading-line">
		<?php _e( "API Url", "emailchef-for-woocommerce" ); ?>
    </h1>

    <p><strong><?php echo WCEC()->get_api_url(); ?></strong></p>

	<?php
	if ( WCEC()->emailchef() ):
		?>

        <h1 class="wp-heading-inline"><?php
			_e( "Policy", "emailchef-for-woocommerce" ); ?></h1>

        <p>
            <label>
				<?php echo WCEC()->emailchef()->get_policy(); ?>
            </label>
        </p>

        <h1 class="wp-heading-inline"><?php
			_e( "List ID", "emailchef-for-woocommerce" ); ?></h1>

        <p>
            <label>
				<?php
				echo $list_id ?: __( "Not found", "emailchef-for-woocommerce" );
				?>
            </label>
        </p>

		<?php
		if ( ! empty( $list_id ) ):
			?>

            <h1 class="wp-heading-inline"><?php
				_e( "Test Custom Fields", "emailchef-for-woocommerce" ); ?></h1>

            <p>
                <textarea cols="50" rows="8" class="large-text">
                            <?php echo json_encode( WCEC()->emailchef()->get_collection(
	                            $list_id
                            ) ); ?>
                        </textarea>
            </p>
            <p>
                <button class="button button-primary" id="emailchef-button-rebuild-customfields">
					<?php _e( "Rebuild Custom Fields", "emailchef-for-woocommerce" ); ?>
                </button>
            </p>

		<?php
		endif;
		?>


	<?php
	endif;
	?>

    <h1 class="wp-heading-inline"><?php
		_e( "Abandoned carts", "emailchef-for-woocommerce" ); ?> </h1>

	<?php
	if ( count( $carts ) > 0 ):
		?>

    <div style="margin: 10px 0;">

        <button class="button button-primary" id="emailchef-button-move-abandoned-carts">
			<?php _e( "Move abandoned carts", "emailchef-for-woocommerce" ); ?>
        </button>

    </div>

        <table class="wp-list-table emailchef-abcart-table widefat fixed striped pages">
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
        </table>
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

</div>
