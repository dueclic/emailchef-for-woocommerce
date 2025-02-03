<div class="ecwc-main-container">
	<div class="ecwc-main-account">
		<div class="ecwc-forms-logo">
			<img src="<?php
			echo plugins_url( "dist/img/logo-compact.svg",
				dirname( __FILE__ ) ); ?>" alt="">
			<div class="ecwc-account-status">
				<div>Account connesso</div>
				<div class="ecwc-account-connected"></div>
			</div>
		</div>
		<div class="ecwc-account-info">
			<span class="flex-grow-1 truncate" title="alessandro@sendblaster.com"><strong>alessandro@sendblaster.com</strong></span>
			<span>
                            <a id="emailchef-disconnect" class="ecwc-account-disconnect" title="Disconnect account">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M280 24c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 240c0 13.3 10.7 24 24 24s24-10.7 24-24l0-240zM134.2 107.3c10.7-7.9 12.9-22.9 5.1-33.6s-22.9-12.9-33.6-5.1C46.5 112.3 8 182.7 8 262C8 394.6 115.5 502 248 502s240-107.5 240-240c0-79.3-38.5-149.7-97.8-193.3c-10.7-7.9-25.7-5.6-33.6 5.1s-5.6 25.7 5.1 33.6c47.5 35 78.2 91.2 78.2 154.7c0 106-86 192-192 192S56 368 56 262c0-63.4 30.7-119.7 78.2-154.7z"></path></svg>
                            </a>
                        </span>
		</div>
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
						<label for="wc_emailchef_list">List <span class="woocommerce-help-tip" tabindex="0" aria-label="Select your destination list or create a new."></span></label>
					</th>
					<td class="forminp forminp-select">
						<select name="" id="" style="min-width: 350px;" class="" tabindex="-1" aria-hidden="true">
							<option value="124312">Star Rating test</option>
							<option value="121034">demo</option>
							<option value="120918" selected="selected">Mi Tienda Online</option>
							<option value="120820">My New Leads</option>
							<option value="104630">My First List</option>
						</select>
						<p class="description "><br><a href="#" id="wc_emailchef_create_list">Add a new destination list.</a></p>
						<div class="ecwc-new-list-container">
							<label>List name</label>
							<input name="wc_emailchef_new_name" id="wc_emailchef_new_name" type="text" dir="ltr" style="min-width:350px;" value="" class="" placeholder="Provide a name for this new list.">
							<label>List description</label>
							<input name="wc_emailchef_new_description" id="wc_emailchef_new_description" type="text" dir="ltr" style="min-width:350px;" value="" class="" placeholder="Provide a description for this new list.">
							<p>By creating a new list, you confirm its compliance with the privacy policy and the CAN-SPAM Act.</p>
							<p class="ecwc-buttons-container">
								<button name="wc_emailchef_save" class="button-primary woocommerce-save-button" id="wc_emailchef_new_save">Create</button>
								<button name="wc_emailchef_undo" class="button woocommerce-undo-button" id="wc_emailchef_undo_save">Undo</button>
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
								<input name="wc_emailchef_sync_customers" id="wc_emailchef_sync_customers" type="checkbox" class="" value="1" checked="checked"> 							</label> 																</fieldset>
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
						<label for="wc_emailchef_lang">Language <span class="woocommerce-help-tip" tabindex="0" aria-label="You can choose your favorite language"></span></label>
					</th>
					<td class="forminp forminp-select">
						<select style="min-width: 350px;" class="" tabindex="-1" aria-hidden="true">
							<option value="it" selected="selected">Italian</option>
							<option value="en">English</option>
						</select>
					</td>
				</tr>
				<tr class="">
					<th scope="row" class="titledesc">
						<label for="wc_emailchef_policy_type">Policy <span class="woocommerce-help-tip" tabindex="0" aria-label="Which policy would you like to use?"></span></label>
					</th>
					<td class="forminp forminp-select">
						<select style="" class="">
							<option value="sopt">Single opt-in</option>
							<option value="dopt" selected="selected">Double opt-in</option>
						</select>
					</td>
				</tr>
				<tr class="single_select_page " style="">
					<th scope="row" class="titledesc">
						<label>Subscription page <span class="woocommerce-help-tip" tabindex="0" aria-label="Page where customer moved after subscribe newsletter in double opt-in"></span></label>
					</th>
					<td class="forminp">
						<select name="wc_emailchef_landing_page" class="wc-enhanced-select-nostd select2-hidden-accessible enhanced" data-placeholder="Select a page…" style="" id="wc_emailchef_landing_page" tabindex="-1" aria-hidden="true">
							<option value=""> </option>
							<option class="level-0" value="2">Sample Page</option>
							<option class="level-0" value="5">Shop</option>
							<option class="level-0" value="6">Cart</option>
							<option class="level-0" value="7">Checkout</option>
							<option class="level-0" value="8">My account</option>
							<option class="level-0" value="275">test wpforms</option>
							<option class="level-0" value="26" selected="selected">Welcome</option>
							<option class="level-0" value="27">Blog</option>
							<option class="level-0" value="291">Refund and Returns Policy</option>
							<option class="level-0" value="343">Landing page</option>
							<option class="level-0" value="111">form</option>
						</select><span class="select2 select2-container select2-container--default" dir="ltr" style="width: 400px;"><span class="selection"><span class="select2-selection select2-selection--single" aria-haspopup="true" aria-expanded="false" tabindex="0" aria-labelledby="select2-wc_emailchef_landing_page-container" role="combobox"><span class="select2-selection__rendered" id="select2-wc_emailchef_landing_page-container" role="textbox" aria-readonly="true" title="Welcome"><span class="select2-selection__clear">×</span>Welcome</span><span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span></span></span><span class="dropdown-wrapper" aria-hidden="true"></span></span>
					</td>
				</tr>
				<tr class="single_select_page " style="">
					<th scope="row" class="titledesc">
						<label>Unsubscription page <span class="woocommerce-help-tip" tabindex="0" aria-label="Page where customer moved after unsubscribe newsletter in double opt-in"></span></label>
					</th>
					<td class="forminp">
						<select name="wc_emailchef_fuck_page" class="wc-enhanced-select-nostd select2-hidden-accessible enhanced" data-placeholder="Select a page…" style="" id="wc_emailchef_fuck_page" tabindex="-1" aria-hidden="true">
							<option value=""> </option>
							<option class="level-0" value="2" selected="selected">Sample Page</option>
							<option class="level-0" value="5">Shop</option>
							<option class="level-0" value="6">Cart</option>
							<option class="level-0" value="7">Checkout</option>
							<option class="level-0" value="8">My account</option>
							<option class="level-0" value="275">test wpforms</option>
							<option class="level-0" value="26">Welcome</option>
							<option class="level-0" value="27">Blog</option>
							<option class="level-0" value="291">Refund and Returns Policy</option>
							<option class="level-0" value="343">Landing page</option>
							<option class="level-0" value="111">form</option>
						</select><span class="select2 select2-container select2-container--default" dir="ltr" style="width: 400px;"><span class="selection"><span class="select2-selection select2-selection--single" aria-haspopup="true" aria-expanded="false" tabindex="0" aria-labelledby="select2-wc_emailchef_fuck_page-container" role="combobox"><span class="select2-selection__rendered" id="select2-wc_emailchef_fuck_page-container" role="textbox" aria-readonly="true" title="Sample Page"><span class="select2-selection__clear">×</span>Sample Page</span><span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span></span></span><span class="dropdown-wrapper" aria-hidden="true"></span></span>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<p class="submit">
			<button name="save" class="woocommerce-save-button components-button is-primary" type="submit" value="Save changes">Save changes</button>
			<input type="hidden" id="_wpnonce" name="_wpnonce" value="9d2922f01a"><input type="hidden" name="_wp_http_referer" value="/wp-admin/admin.php?page=wc-settings&amp;tab=emailchef">
		</p>
	</div>

</div>
