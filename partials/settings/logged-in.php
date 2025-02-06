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
$policy    = $wcec->emailchef()->get_policy();

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
                WC_EMAILCHEF_FILE  ); ?>" alt="">
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
        <div>
             <span class="flex-grow-1">
                <a class="button button-primary" href="<?php echo esc_url( add_query_arg(
	                [ 'source' => 'emailchef-for-woocommerce', 'paged' => 1 ],
	                admin_url( '/admin.php?page=wc-status&tab=logs' )
                ) ); ?>"><?php _e( "Show Logs", "emailchef-for-woocommerce" ); ?></a>
            </span>
        </div>
        <div>
             <span class="flex-grow-1">
                <button type="button" id="wc_emailchef_sync_now" class="button button-secondary">
                    <?php _e("Manual Sync", "emailchef-for-woocommerce"); ?>
                </button>
            </span>
        </div>
		<?php
		if ( isset( $_GET['debug'] ) ):
			?>
            <div>
             <span class="flex-grow-1">
                <a class="button button-primary"
                   href="<?php echo admin_url( '/admin.php?page=emailchef-debug' ); ?>"><?php _e( "Debug", "emailchef-for-woocommerce" ); ?></a>
            </span>
            </div>
		<?php
		endif;
		?>
    </div>
    <div class="ecwc-main-forms">
        <h1>Emailchef for Woocommerce settings</h1>
        <p>Description</p>
        <div class="emailchef-form card accordion-container">
            <h2>Emailchef List Settings</h2>
            <p>Info about this section...</p>
            <table class="form-table">
                <tbody>
                <tr class="" style="">
                    <th scope="row" class="titledesc">
                        <label for="wc_emailchef_list">List <?php echo wc_help_tip( "Select your destination list or create a new." ); ?></label>
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
                        <p class="description "><br><a href="#" id="wc_emailchef_create_list">Add a new destination
                                list.</a></p>
                        <div class="ecwc-new-list-container">
                            <label>List name</label>
                            <input name="wc_emailchef_new_name" id="wc_emailchef_new_name" type="text" dir="ltr"
                                   style="min-width:350px;" value="" class=""
                                   placeholder="Provide a name for this new list.">
                            <label>List description</label>
                            <input name="wc_emailchef_new_description" id="wc_emailchef_new_description" type="text"
                                   dir="ltr" style="min-width:350px;" value="" class=""
                                   placeholder="Provide a description for this new list.">
                            <p>By creating a new list, you confirm its compliance with the privacy policy and the
                                CAN-SPAM Act.</p>
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
                    <th scope="row" class="titledesc">Sync customers</th>
                    <td class="forminp forminp-checkbox ">
                        <fieldset>
                            <legend class="screen-reader-text"><span>Sync customers</span></legend>
                            <label for="wc_emailchef_sync_customers">
                                <input name="wc_emailchef_sync_customers" id="wc_emailchef_sync_customers"
                                       type="checkbox" class="" value="1" checked="checked"> </label></fieldset>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="emailchef-form card accordion-container">
            <h2>Emailchef Subscription settings</h2>
            <p>Info about this section...</p>
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
								__( "Page where customer moved after subscribe newsletter in double opt-in", "emailchef-for-woocommerce" )
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
                            page <?php echo wc_help_tip( "Page where customer moved after unsubscribe newsletter in double opt-in" ); ?></label>
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

        <div class="ecwc-text-center submit">
            <button name="save" disabled class="woocommerce-save-button components-button is-primary" type="submit"
                    value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
        </div>
    </div>

</div>
