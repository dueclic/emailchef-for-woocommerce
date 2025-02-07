<?php

/**
 * @var $wcec WC_Emailchef_Plugin
 */

$emailchef = WCEC()->emailchef(
	wc_ec_get_option_value(
		'consumer_key'
	),
	wc_ec_get_option_value(
		'consumer_secret'
	)
);
$account   = $emailchef->account();
$lists     = $emailchef->lists();
$policy    = $emailchef->get_policy();

$policy_types = [
	'sopt' => __( "Single opt-in", "emailchef-for-woocommerce" ),
	'dopt' => __( "Double opt-in", "emailchef-for-woocommerce" )
];

if ( $policy !== 'premium' ) {
	unset( $policy_types['sopt'] );
}


?>

<div class="ecwc-main-container">
    <div class="ecwc-main-account">
        <div class="ecwc-forms-logo">
            <img src="<?php
			echo plugins_url( "/dist/img/logo-compact.svg",
				WC_EMAILCHEF_FILE ); ?>" alt="">
            <div class="ecwc-account-status">
                <div><?php _e( "Account connected", "emailchef-for-wocommerce" ); ?></div>
                <div class="ecwc-account-connected"></div>
            </div>
        </div>
        <div class="ecwc-account-info">
            <span class="flex-grow-1 truncate"
                  title="<?php echo $account['email']; ?>"><strong><?php echo $account['email']; ?></strong>
            </span>
            <span>
                <a id="emailchef-disconnect" class="ecwc-account-disconnect"
                   title="<?php _e( "Disconnect account", "emailchef-for-wocommerce" ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path
                                d="M280 24c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 240c0 13.3 10.7 24 24 24s24-10.7 24-24l0-240zM134.2 107.3c10.7-7.9 12.9-22.9 5.1-33.6s-22.9-12.9-33.6-5.1C46.5 112.3 8 182.7 8 262C8 394.6 115.5 502 248 502s240-107.5 240-240c0-79.3-38.5-149.7-97.8-193.3c-10.7-7.9-25.7-5.6-33.6 5.1s-5.6 25.7 5.1 33.6c47.5 35 78.2 91.2 78.2 154.7c0 106-86 192-192 192S56 368 56 262c0-63.4 30.7-119.7 78.2-154.7z"></path></svg>
                </a>
            </span>

        </div>
        <hr class="ecwc-hr-separator">
        <div>
            <p><?php _e( "Woocommerce users usually sync automatically with Emailchef. If an issue arises or you need an immediate update, use the button below for manual sync.", "emailchef-for-woocommerce" ); ?></p>
            <p class="ecwc-text-center">
                <button <?php disabled( wc_ec_get_option_value( 'list' ), null ); ?> type="button"
                                                                                     id="wc_emailchef_sync_now"
                                                                                     class="button button-secondary">
					<?php _e( "Manual Sync Now", "emailchef-for-woocommerce" ); ?>
                </button>
            </p>
        </div>
        <hr class="ecwc-hr-separator">
        <div>
            <p>
                <a href="<?php echo esc_url( add_query_arg(
					[ 'source' => 'emailchef-for-woocommerce', 'paged' => 1 ],
					admin_url( '/admin.php?page=wc-status&tab=logs' )
				) ); ?>" target="_blank"><?php _e( "Show Logs", "emailchef-for-woocommerce" ); ?></a>
            </p>
        </div>
		<?php
		if ( isset( $_GET['debug'] ) ):
			?>
            <hr class="ecwc-hr-separator">
            <div>
                <p class="ecwc-text-center">
                    <a class="button button-primary"
                       href="<?php echo admin_url( '/admin.php?page=emailchef-debug' ); ?>"><?php _e( "Debug", "emailchef-for-woocommerce" ); ?></a>
                </p>
            </div>
		<?php
		endif;
		?>
    </div>
    <div class="ecwc-main-forms">
        <h1>Emailchef for Woocommerce settings</h1>
        <p>Welcome to the Emailchef Integration section for Woocommerce. In this crucial Woocommerce plugin, we offer
            you the convenience of effortlessly synchronizing your Woocommerce customers with your preferred Emailchef
            list. By taking advantage of this feature, you will ensure that your email marketing efforts are always
            up-to-date and targeting the right audience.</p>
        <div class="emailchef-form card accordion-container">
            <h2>Emailchef List Settings</h2>
            <p>Simply select the Emailchef list that aligns with your campaign objectives, and the plugin will handle
                the rest. Our seamless synchronization process automatically updates your chosen list with new users,
                modifications to existing user information, and any other relevant changes. This not only saves you
                valuable time but also enhances the effectiveness of your communication strategies.</p>
            <table class="form-table">
                <tbody>
                <tr class="" style="">
                    <th scope="row" class="titledesc">
                        <label for="wc_emailchef_list">Emailchef
                            List <?php echo wc_help_tip( "Choose an existing Emailchef list to sync with, or opt to set up a brand-new list for immediate use." ); ?></label>
                    </th>
                    <td class="forminp forminp-select">

                        <select
                                data-placeholder="<?php _e( "Select a list", "emailchef-for-woocommerce" ); ?>"
                                name="<?php echo esc_attr( wc_ec_get_option_name( "list" ) ); ?>"
                                id="<?php echo esc_attr( wc_ec_get_option_name( "list" ) ); ?>"
                                class="wc-enhanced-select-nostd" style="min-width: 350px;" tabindex="-1"
                                aria-hidden="true"
                        >
                            <option></option>
							<?php
							foreach ( $lists as $list ):
								?>
                                <option
                                        value="<?php echo $list['id']; ?>"
									<?php
									selected( wc_ec_get_option_value( "list" ), (string) $list['id'] );

									?>
                                ><?php echo esc_html( $list['name'] ); ?></option>
							<?php
							endforeach;
							?>
                        </select>
                        <p class="description "><br><a href="#" id="wc_emailchef_create_list">Add a new Emailchef
                                destination
                                list</a></p>
                        <div class="ecwc-new-list-container">
                            <label>List name</label>
                            <input name="wc_emailchef_new_name" id="wc_emailchef_new_name" type="text" dir="ltr"
                                   style="min-width:350px;" value="" class=""
                                   placeholder="Provide a name for this new list.">
                            <label>List description</label>
                            <input name="wc_emailchef_new_description" id="wc_emailchef_new_description" type="text"
                                   dir="ltr" style="min-width:350px;" value="" class=""
                                   placeholder="Provide a description for this new list.">
                            <p>By setting up a new list within Emailchef, you acknowledge and affirm adherence to
                                Emailchef's <a href="https://emailchef.com/privacy-policy/" target="_blank">privacy
                                    policy</a> and <a href="https://emailchef.com/terms-of-use/" target="_blank">terms
                                    of use</a>, as well as compliance with the CAN-SPAM Act.</p>
                            <p class="ecwc-buttons-container">
                                <button type="button" name="wc_emailchef_save"
                                        class="button-primary woocommerce-save-button"
                                        id="wc_emailchef_new_save">Create
                                </button>
                                <button type="button" name="wc_emailchef_undo" class="button woocommerce-undo-button"
                                        id="wc_emailchef_undo_save">Undo
                                </button>
                            </p>
                        </div>
                    </td>
                </tr>
                <tr class="" style="">
                    <th scope="row" class="titledesc">Sync existing customers</th>
                    <td class="forminp forminp-checkbox ">
                        <fieldset>
                            <legend class="screen-reader-text"><span>Sync existing customers</span></legend>
                            <label for="wc_emailchef_sync_customers">
                                <input name="wc_emailchef_sync_customers" id="wc_emailchef_sync_customers"
                                       type="checkbox" class="" value="1">Sync existing Woocommerce customers on
                                save</label></fieldset>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="emailchef-form card accordion-container">
            <h2>Emailchef Subscription settings</h2>
            <p>Customize your subscriber's journey by defining the pages they are directed to after subscribing or
                unsubscribing, and determine whether to use a single or a double opt-in approach based on your
                preferences and compliance requirements.</p>
            <table class="form-table">
                <tbody>

                <tr class="">
                    <th scope="row" class="titledesc">
                        <label for="<?php echo esc_attr( wc_ec_get_option_name( "policy_type" ) ); ?>">Policy
							<?php
							echo wc_help_tip(
								__( "Which policy would you like to use?", "emailchef-for-woocommerce" )
							);
							?>
                        </label>
                    </th>
                    <td class="forminp forminp-select">
                        <select name="<?php echo esc_attr( wc_ec_get_option_name( "policy_type" ) ); ?>"
                                id="<?php echo esc_attr( wc_ec_get_option_name( "policy_type" ) ); ?>"
                                aria-hidden="true">

							<?php
							foreach ( $policy_types as $value => $name ):
								?>
                                <option value="<?php echo $value; ?>" <?php selected(
									wc_ec_get_option_value( "policy_type" ),
									$value
								); ?>><?php echo esc_html( $name ); ?></option>
							<?php
							endforeach;
							?>

                        </select>
                    </td>
                </tr>
                <tr class="single_select_page " style="">
                    <th scope="row" class="titledesc">
                        <label>Subscription page <?php echo wc_help_tip(
								__( "Page to redirect the customer to after confirming their subscription with the double opt-in method", "emailchef-for-woocommerce" )
							); ?></label>
                    </th>
                    <td class="forminp">

						<?php
						echo wc_ec_get_dropdown_pages(
							"subscription_page",
							[
								'show_option_none' => ''
							]
						);
						?>

                    </td>
                </tr>
                <tr class="single_select_page " style="">
                    <th scope="row" class="titledesc">
                        <label>Unsubscription
                            page <?php echo wc_help_tip( "Page to which the customer is redirected after unsubscribing from the newsletter" ); ?></label>
                    </th>
                    <td class="forminp">
						<?php
						echo wc_ec_get_dropdown_pages(
							"fuck_page",
							[
								'show_option_none' => ''
							]
						);
						?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="emailchef-form card accordion-container">
            <h2>Abandoned Cart settings</h2>
            <p>Recover lost sales with Emailchef plugin's abandoned cart feature. Connect with Emailchef and remind
                customers to complete their purchases by setting a time frame for abandoned carts.</p>
            <table class="form-table">
                <tbody>

                <tr class="">
                    <th scope="row" class="titledesc">
                        <label for="<?php echo esc_attr( wc_ec_get_option_name( "cron_end_interval_value" ) ); ?>">Cart
                            Abandoned After
							<?php
							echo wc_help_tip(
								__( "Enter the number of hours after which a cart is considered abandoned.", "emailchef-for-woocommerce" )
							);
							?>
                        </label>
                    </th>
                    <td class="forminp forminp-input">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="number" style="max-width: 100px" min="0"
                                   name="<?php echo esc_attr( wc_ec_get_option_name( "cron_end_interval_value" ) ); ?>"
                                   id="<?php echo esc_attr( wc_ec_get_option_name( "cron_end_interval_value" ) ); ?>"
                                   value="<?php echo wc_ec_get_option_value( "cron_end_interval_value" ); ?>">
							<?php


							switch ( wc_ec_get_abandoned_carts_end_unit() ):
								case "DAY":
									esc_html_e( "days", "emailchef-for-woocommerce" );
									break;
								case "HOUR":
								default:
									esc_html_e( "hours", "emailchef-for-woocommerce" );
									break;
							endswitch;
							?>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="submit">
            <button name="save" disabled class="woocommerce-save-button components-button is-primary" type="submit"
                    value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
        </div>
    </div>

</div>
