<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once(WC_EMAILCHEF_DIR . 'includes/class-wc-emailchef-api.php');

class WC_Emailchef extends WC_Emailchef_Api
{

    private $new_custom_id;

    private function get_custom_fields()
    {
        return require(WC_EMAILCHEF_DIR . "conf/custom_fields.php");
    }

    /**
     *
     * Emailchef sync initial list
     *
     * @param $list_id
     * @param bool $all if not all, only custom fields will be created
     *
     * @return bool
     */

    public function sync_list($list_id, $all = true)
    {

        global $wpdb;

        wc_set_time_limit(0);

        $customers = get_users("role=customer");

        $this->initialize_custom_fields($list_id);

        if ($all) {
            $data = array();

            WCEC()->log(sprintf(__("[START] Synchronization and custom fields creation for Emailchef list %d",
                "emailchef-for-woocommerce"), $list_id));

            foreach ($customers as $customer) {
                $curCustomer = array();

                $wpdb->check_connection();

                $wc_customer = new WC_Emailchef_Customer($customer->ID);

                foreach (
                    $wc_customer->sync_array() as $placeholder => $value
                ) {
                    if ($placeholder == "user_email") {
                        $placeholder = "email";
                    }

                    $curCustomer[] = array(
                        "placeholder" => $placeholder,
                        "value" => $value
                    );
                }

                if (count($data) > 100) {
                    WCEC()->log(sprintf(__("[PROGRESS] Synchronization of the list %d, chunk of %d contacts.",
                        "emailchef-for-woocommerce"), $list_id,
                        count($data)));

                    if (!$this->import($list_id, $data)) {
                        WCEC()->log(sprintf(__("[ERROR] Synchronization of the list %d, details : %s",
                            "emailchef-for-woocommerce"), $list_id,
                            $this->lastError));
                    }

                    $data = array();
                }
                $data[] = $curCustomer;
            }

            if (count($data) > 0) {
                WCEC()->log(sprintf(__("[PROGRESS] Synchronization of the list %d, chunk of %d contacts.",
                    "emailchef-for-woocommerce"), $list_id,
                    count($data)));

                if (!$this->import($list_id, $data)) {
                    WCEC()->log(sprintf(__("[ERROR] Synchronization of the list %d, details : %s",
                        "emailchef-for-woocommerce"), $list_id,
                        $this->lastError));
                }
            }

            return true;

        }
        return true;

    }

    /**
     *
     * Emailchef sync order change
     *
     * @param $list_id
     * @param $customer_id
     *
     * @return bool
     */

    public function sync_order_change($list_id, $customer_id)
    {

        $ab_cart = array(

            'ab_cart_is_abandoned_cart' => false,
            'ab_cart_prod_name_pr_hr' => "",
            'ab_cart_prod_desc_pr_hr' => "",
            'ab_cart_prod_pr_pr_hr' => "",
            'ab_cart_prod_url_pr_hr' => "",
            'ab_cart_prod_url_img_pr_hr' => "",
            'ab_cart_prod_id_pr_hr' => "",
            'ab_cart_date' => "",

        );

        $wc_customer = new WC_Emailchef_Customer($customer_id);

        $send_data = array_merge($wc_customer->sync_array(), $ab_cart);
        unset($send_data['newsletter']);

        return $this->upsert_customer($list_id, $send_data);
    }

    private function ordered_product_ids($items)
    {

        $ids = array();

        foreach ($items as $item) {
            $ids[] = $item['product_id'];
        }

        return implode(",", $ids);

    }

    public function sync_guest($list_id, $order_id, $newsletter)
    {
        $order = wc_get_order($order_id);

        $sendData = array(
            'source' => WCEC()->get_platform(),
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'billing_company' => $order->get_billing_company(),
            'billing_address_1' => $order->get_billing_address_1(),
            'billing_postcode' => $order->get_billing_postcode(),
            'billing_city' => $order->get_billing_city(),
            'billing_phone' => $order->get_billing_phone(),
            'billing_state' => $order->get_billing_state(),
            'billing_country' => $order->get_billing_country(),
            'customer_type' => 'Guest',
            'user_email' => $order->get_billing_email(),
            'currency' => get_woocommerce_currency(),
            'total_ordered' => $order->get_total(),
            'total_ordered_30d' => $order->get_total(),
            'total_ordered_12m' => $order->get_total(),
            'total_orders' => 1,
            'all_ordered_product_ids' => $this->ordered_product_ids($order->get_items()),
            'latest_order_product_ids' => $this->ordered_product_ids($order->get_items()),
            'latest_order_id' => $order_id,
            'latest_order_amount' => $order->get_total(),
            'latest_order_date' => $order->get_date_modified()->date_i18n(),
            'latest_order_status' => wc_ec_get_order_status_name("wc-" . $order->get_status()),
            'newsletter' => $newsletter ? "pending" : "no",
            'store_registration_date' => wc_ec_get_user_registration_byorder($order)
        );

        if ($order->get_status() != "pending") {
            unset($sendData['newsletter']);
        }

        if ($order->get_status() == "completed") {
            $other_data = array(
                'latest_shipped_order_id' => $order_id,
                'latest_shipped_order_date' => $order->get_date_modified()->date_i18n(),
                'latest_shipped_order_status' => wc_ec_get_order_status_name("wc-" . $order->get_status())
            );
        } else {
            $other_data = array(
                'latest_shipped_order_id' => "",
                'latest_shipped_order_date' => "",
                'latest_shipped_order_status' => ""
            );
        }

        $sendData = array_merge($sendData, $other_data);

        return $this->upsert_customer($list_id, $sendData);

    }

    public function import($list_id, $customers)
    {

        $args = array(
            "instance_in" => array(
                "contacts" => $customers,
                "notification_link" => ""
            )
        );

        add_filter("ec_wc_get_args", array($this, "filter_args_json"));

        $update = $this->best_get("/lists/" . $list_id . "/import", $args, true, "POST");

        if (isset($update['status']) && $update['status'] == "OK") {
            return true;
        }

        remove_filter("ec_wc_get_args", array($this, "filter_args_json"));

        $this->lastError = $update['message'];

        return false;

    }

    public function maybe_empty_cart($list_id, $customer_id, $order_id)
    {

        $wc_customer = new WC_Emailchef_Customer($customer_id);

        $path = "/contacts";
        $route = sprintf(
            "%s?query_string=%s&limit=10&offset=0&list_id=%d&orderby=e&ordertype=a",
            $path,
            $wc_customer->getEmail(),
            $list_id
        );
        $ec_customer = $this->best_get($route, array(), true, "GET");
        $ec_id = $ec_customer[0]['id'];

        $route = sprintf("%s/%d?list_id=%d", $path, $ec_id, $list_id);
        $in_ec = $this->best_get($route, array(), true, "GET");


        $customFields = $in_ec['customFields'];
        $placeholders = array_column($customFields, 'place_holder');
        $idKey = array_search('ab_cart_prod_id_pr_hr', $placeholders);
        $ec_pd_id = $customFields[$idKey]['value'];

        $order = wc_get_order($order_id);

        $customer = array(

            'user_email' => $wc_customer->getEmail(),
            'first_name' => $wc_customer->meta()->get("first_name"),
            'last_name' => $wc_customer->meta()->get("last_name"),
            'ab_cart_prod_name_pr_hr' => "",
            'ab_cart_prod_desc_pr_hr' => "",
            'ab_cart_prod_pr_pr_hr' => "",
            'ab_cart_prod_url_pr_hr' => "",
            'ab_cart_prod_url_img_pr_hr' => "",
            'ab_cart_prod_id_pr_hr' => "",
            'ab_cart_date' => "",

        );

        foreach ($order->get_items() as $item) {

            if ($item['product_id'] == $ec_pd_id) {
                return $this->update_customer($list_id, $customer, $ec_id, true);
            }

        }

        return true;

    }

    public function sync_cart($list_id, $send_data)
    {
        return $this->upsert_customer($list_id, $send_data);
    }

    public function sync_user_change($list_id, $customer_id, $newsletter)
    {
        $wc_customer = new WC_Emailchef_Customer($customer_id);
        return $this->upsert_customer($list_id, $wc_customer->sync_user($newsletter));
    }

    /**
     *
     * Emailchef gateway send email
     *
     * @param $to
     * @param $subject
     * @param $message
     *
     * @return bool
     */

    public function em_mail($to, $subject, $message)
    {

        $senders = $this->best_get("/senders", array(), true, "GET");

        $args = array(

            "instance_in" => array(
                "sender_id" => $senders[0]['id'],
                "to" => array(
                    "email" => $to,
                    "name" => $to,
                ),
                "subject" => $subject,
                "text_body" => $message,
                "html_body" => $message,
            ),

        );

        $response = $this->best_get("/sendmail", $args, true, "POST");

        if (isset($response['status']) && $response['status'] == "OK") {
            return true;
        } else {
            $this->lastError = $response['message'];

            return false;
        }

    }

    /**
     *
     * Insert customer
     *
     * @param $list_id
     * @param $customer
     *
     * @return bool
     */

    private function insert_customer($list_id, $customer)
    {

        $customer = $this->_prepare_customer_data($customer, $list_id);

        $collection = $this->get_collection($list_id);

        $custom_fields = array_map(
            function ($field) use ($customer) {

                $field['value'] = $customer[$field['place_holder']];

                if ($field['value'] == null) {
                    $field['value'] = "";
                }

                return $field;

            },
            $collection
        );

        $args = array(

            "instance_in" => array(
                "list_id" => $list_id,
                "status" => "ACTIVE",
                "email" => $customer['user_email'],
                "firstname" => $customer['first_name'],
                "lastname" => $customer['last_name'],
                "custom_fields" => $custom_fields,
                "mode" => "ADMIN",
            ),

        );

        if (!isset($customer['first_name']) || empty($args['instance_in']['firstname'])) {
            unset($args['instance_in']['firstname']);
        }

        if (!isset($customer['last_name']) || empty($args['instance_in']['lastname'])) {
            unset($args['instance_in']['lastname']);
        }

        $response = $this->best_get("/contacts", $args, true, "POST");

        if (isset($response['contact_added_to_list']) && $response['contact_added_to_list']) {
            return true;
        }

        $this->lastError = $response['message'];

        return false;

    }

    public function dopt_confirm($list_id, $customer_id, $firstname, $lastname, $opt_in)
    {

        $path = "/contacts";
        $route = sprintf("%s/%d", $path, $customer_id);

        $collection = $this->get_collection($list_id);

        $dKey = array_search("newsletter", array_column($collection, "place_holder"));

        if ($opt_in == 1) {
            $collection[$dKey]['value'] = 'yes';
        } elseif ($opt_in == 2) {
            $collection[$dKey]['value'] = 'no';
        }

        $args = array(

            "instance_in" => array(
                "list_id" => $list_id,
                "firstname" => $firstname,
                "lastname" => $lastname,
                "status" => "ACTIVE",
                "custom_fields" => array(
                    $collection[$dKey],
                ),
                "mode" => "ADMIN",
            ),

        );

        $update = $this->best_get($route, $args, true, "PUT");

        if (isset($update['status']) && $update['status'] == "OK") {
            return true;
        }

        $this->lastError = $update['message'] . ": " . $update['more_info'];

        return false;

    }

    private function _prepare_customer_data($customer, $list_id, $ec_id = 0){
	    return apply_filters('emailchef_customer_data', $customer, $list_id, $ec_id);
	}

    private function update_customer($list_id, $customer, $ec_id)
    {

        $customer = $this->_prepare_customer_data($customer, $list_id, $ec_id);

        $path = "/contacts";
        $route = sprintf("%s/%d", $path, $ec_id);

        $custom_fields = array();
        $collection = $this->get_collection($list_id);

        foreach ($collection as $custom) {

            $my_custom = $custom;

            if (!isset($customer[$my_custom['place_holder']])) {

                if ($my_custom['place_holder'] == "latest_shipped_order_id") {
                    $my_custom['value'] = "";
                    $custom_fields[] = $my_custom;
                }

                if ($my_custom['place_holder'] == "latest_shipped_order_date") {
                    $my_custom['value'] = "";
                    $custom_fields[] = $my_custom;
                }

                if ($my_custom['place_holder'] == "latest_shipped_order_status") {
                    $my_custom['value'] = "";
                    $custom_fields[] = $my_custom;
                }

                continue;

            }

            $my_custom['value'] = $customer[$my_custom['place_holder']];

            $custom_fields[] = $my_custom;

        }

        $args = array(

            "instance_in" => array(
                "list_id" => $list_id,
                "status" => "ACTIVE",
                "email" => $customer['user_email'],
                "firstname" => $customer['first_name'],
                "lastname" => $customer['last_name'],
                "custom_fields" => $custom_fields,
                "mode" => "ADMIN",
            ),

        );

        $update = $this->best_get($route, $args, true, "PUT");

        if (isset($update['status']) && $update['status'] == "OK") {
            return true;
        }

        $this->lastError = $update['message'];

        return false;

    }

    public function upsert_customer($list_id, $customer, $opt_in = 0)
    {

        $path = "/contacts";

        $route = sprintf(
            "%s?query_string=%s&limit=10&offset=0&list_id=%d&orderby=e&ordertype=a",
            $path,
            $customer['user_email'],
            $list_id
        );

        $ec_customer = $this->best_get($route, array(), true, "GET");

        if (empty($ec_customer)) {
            return $this->insert_customer($list_id, $customer);
        }

        if ($opt_in != 0) {
            return $this->dopt_confirm(
                $list_id,
                $ec_customer[0]['id'],
                $customer['firstname'],
                $customer['lastname'],
                $opt_in
            );
        }

        return $this->update_customer($list_id, $customer, $ec_customer[0]['id']);


    }

    public function initialize_custom_fields($list_id)
    {

        $collection = $this->get_collection($list_id);

        $new_custom_fields = array();

        foreach ($this->get_custom_fields() as $place_holder => $custom_field) {

            $type = $custom_field['type'];
            $name = $custom_field['name'];
            $options = (isset($custom_field['options']) ? $custom_field['options'] : array());
            $default_value = (isset($custom_field['default_value']) ? $custom_field['default_value'] : "");

            /**
             *
             * Check if is predefined
             * if it is continue
             *
             */

            if ($type == "predefined") {
                continue;
            }

            /**
             *
             * Check if a custom field exists by placeholder
             *
             */

            $cID = array_search($place_holder, array_column($collection, "place_holder"));

            if ($cID !== false) {

                /**
                 *
                 * Check if the type of custom fields is valid
                 *
                 */

                $data_type = $collection[$cID]['data_type'];
                $data_id = $collection[$cID]['id'];

                if ($type != $data_type) {
                    $this->delete_custom_field($data_id);
                } else {
                    $new_custom_fields[] = $data_id;
                    continue;
                }

            }

            $this->create_custom_field($list_id, $type, $name, $place_holder, $options, $default_value);
            $new_custom_fields[] = $this->new_custom_id;

        }

        /**
         *
         * Check if there are fields in emailChef
         * not present in @private $custom_fiels
         *
         * If fields are present delete
         *
         */

        /*$ec_id_custom_fields = array_column($collection, "id");
        $diff                = array_diff($ec_id_custom_fields, $new_custom_fields);

        foreach ($diff as $custom_id) {
            $this->delete_custom_field($custom_id);
        }*/

        return true;

    }

    public function filter_args_json($args)
    {

        $args['headers'] = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        );
        $args['body'] = json_encode($args['body']);

        return $args;

    }

    public function delete_custom_field($field_id)
    {

        $route = sprintf("/customfields/%d", $field_id);

        add_filter("ec_wc_get_args", array($this, "filter_args_json"));

        $status = $this->best_get($route, array(), true, "DELETE");

        if ($status !== "OK") {
            $this->lastError = $status['message'];
        }

        remove_filter("ec_wc_get_args", array($this, "filter_args_json"));

        return ($status == "OK");

    }

    public function create_custom_field(
        $list_id,
        $type,
        $name,
        $placeholder,
        $options = array(),
        $default_value = ""
    )
    {

        $route = sprintf("/lists/%d/customfields", $list_id);

        $args = array(

            "instance_in" => array(
                "data_type" => $type,
                "name" => ($name == "" ? $placeholder : $name),
                "place_holder" => $placeholder,
                "default_value" => $default_value,
            ),

        );

        if ($type == "select") {
            $args["instance_in"]["options"] = $options;
        }

        add_filter("ec_wc_get_args", array($this, "filter_args_json"));

        $response = $this->best_get($route, $args, true, "POST");

        remove_filter("ec_wc_get_args", array($this, "filter_args_json"));

        if (isset($response['status']) && $response['status'] == "OK") {

            $this->new_custom_id = $response['custom_field_id'];

            return true;
        }

        $this->lastError = $response['message'];

        return false;


    }

    public function get_collection($list_id)
    {
        $route = sprintf("/lists/%d/customfields", $list_id);

        return $this->best_get($route, array(), true, "GET");
    }

    public function get_policy()
    {

        $account = $this->best_get("/accounts/current?with_policy_details=1", array(), true, "GET");

		if ( isset($account['policy_details'])){
			if ($account['policy_details']['single_optin'] ){
				return 'premium';
			}
			return 'safe';
		}

        return $account['mode'];

    }

	public function getApiUrl(){
		return $this->api_url;
	}

    public function __construct($username, $password)
    {
        parent::__construct($username, $password);
    }

    private function best_get($route, $args, $asArray, $type = "POST")
    {

        if ($asArray) {
            return $this->getDecodedJson($route, $args, $type);
        }

        return $this->get($route, $args, $type);

    }

    /**
     * Get integrations
     *
     * @return string
     */

    public function get_meta_integrations()
    {
	    return $this->best_get("/meta/integrations", array(), true, "GET");
    }


    /**
     * Get integrations from Emailchef List
     *
     * @param $list_id
     *
     * @return mixed
     */

    public function get_integrations($list_id)
    {
        $route = sprintf("/lists/%d/integrations", $list_id);

        return $this->best_get($route, array(), true, "GET");
    }

    /**
     * Upsert integrations yo Emailchef List
     *
     * @param $list_id
     *
     * @return mixed
     */

    public function upsert_integration($list_id)
    {
        $integrations = $this->get_integrations($list_id);
        foreach ($integrations as $integration) {
            if ($integration["id"] == 3 && $integration["website"] == get_home_url()) {
                return $this->update_integration($integration["row_id"], $list_id);
            }
        }

        return $this->create_integration($list_id);
    }

    /**
     * Upsert integrations yo Emailchef List
     *
     * @param $list_id
     * @param $integration_id
     *
     * @return mixed
     */

    public function update_integration($integration_id, $list_id)
    {

        $args = array(

            "instance_in" => array(
                "list_id" => $list_id,
                "integration_id" => 3,
                "website" => get_site_url(),
            )

        );

        $response = $this->best_get("/integrations/" . $integration_id, $args, true, "PUT");

        if ($response['status'] != "OK") {
            $this->lastError = $response['message'];
            $this->lastResponse = $response;

            return false;
        }

        return $response['integration_id'];

    }

    /**
     * Get integrations from Emailchef List
     *
     * @param $list_id
     *
     * @return mixed
     */

    public function create_integration($list_id)
    {

        $args = array(

            "instance_in" => array(
                "list_id" => $list_id,
                "integration_id" => 3,
                "website" => get_site_url(),
            )

        );

        $response = $this->best_get("/integrations", $args, true, "POST");

        if ($response['status'] != "OK") {
            $this->lastError = $response['message'];
            $this->lastResponse = $response;

            return false;
        }

        return $response['id'];

    }

    public function lists($args = array(), $asArray = true)
    {
        return $this->best_get("/lists", $args, $asArray, "GET");
    }

    public function wrap_list($args = array())
    {

        /**
         * Implemented transient
         */

        if (!$results = get_transient('ecwc_lists')) {

            $args['offset'] = 0;
            $args['orderby'] = 'cd';
            $args['ordertype'] = 'd';

            if (!array_key_exists('limit', $args)) {
                $args['limit'] = 100;
            }

            $lists = $this->lists($args);

            if (!$lists) {
                return [];
            }

            $results = array();

            foreach ($lists as $list) {

                $results[$list['id']] = $list['name'];

            }

            set_transient('sswcmc_lists', $results, 60 * 15 * 1);

        }

        return $results;

    }

    public function account($asArray = true)
    {
        return $this->best_get("/accounts/current", array(), $asArray, "GET");
    }

    public function create_list($name, $description, $asArray = true)
    {

        $args = array(

            "instance_in" => array(
                "list_name" => $name,
                "integrations" => array(
                    array(
                        "integration_id" => 3,
                        "website" => get_site_url()
                    )
                )
            ),

        );

        if ($description != "") {
            $args["instance_in"]["list_description"] = $description;
        }

        $response = $this->best_get("/lists", $args, $asArray, "POST");

        if ($response['status'] != "OK") {
            $this->lastError = $response['message'];

            return false;
        }

        delete_transient('sswcmc_lists');

        return $response['list_id'];

    }

}
