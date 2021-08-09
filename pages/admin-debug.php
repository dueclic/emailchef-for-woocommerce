<div class="wrap">

    <h1 class="wp-heading-inline"><?php
		_e( "Abandoned carts", "emailchef-for-woocommerce" ); ?></h1>

    <table class="wp-list-table emailchef-abcart-table widefat fixed striped pages">
        <thead>
        <tr>
            <th>
				<?php
				_e( "User" ); ?>
            </th>
            <th>
				<?php
				_e( "Email" ); ?>
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
				_e( "Force Sync", "emailchef-for-woocommerce" );
				?>
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

			$customer_title = $customer->get_first_name() . " " . $customer->get_last_name();
			$customer_search_encode = urlencode($customer_title);

			$product = wc_get_product( $cart['product_id'] );
			?>
            <tr>
                <td>
                    <p>
                        <a target="_blank" href="<?php echo admin_url("admin.php?page=wc-admin&path=%2Fcustomers&search=".$customer_search_encode); ?>">
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
                    <button class="button button-primary button-force-sync"
                            data-user-id="<?php
					        echo $cart['user_id']; ?>" data-user-email="<?php echo $cart['user_email'];?>">
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
</div>
