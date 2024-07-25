<?php

class WC_Emailchef_Customer
{

    private $id;
    private $meta = false;
    private $queue = array();

    /**
     * @var WC_Order
     */

    private $last_order = false;

    /**
     * @var WC_Order
     */

    private $last_ship_order = false;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function meta()
    {
        $this->meta = get_user_meta($this->id);

        return $this;
    }

    public function lastOrder()
    {

        if ($this->last_order === false) {
            $this->last_order = wc_get_customer_last_order($this->id);
        }

        return $this->last_order;
    }

    public function lastShippedOrder()
    {

        if ($this->last_ship_order === false) {
            $this->last_ship_order = wc_get_customer_last_order($this->id);
        }

        return $this->last_ship_order;
    }

    public function get($meta)
    {

        if ($this->meta === false) {
            $this->meta();
        }

		if (isset($this->meta[$meta])){
			return esc_html($this->meta[$meta][0]);
		}

		return "";

    }

    public function getCurrency()
    {
        return get_woocommerce_currency();
    }

    public function totalOrdered($interval = 0)
    {
        return wc_ec_nf_or_empty(wc_ec_get_total_by_days($this->id, $interval));
    }

    public function totalOrders()
    {
        return wc_get_customer_order_count($this->id);
    }

    public function getEmail()
    {
        return get_userdata($this->id)->user_email;
    }

    public function sync_array($newsletter = "no")
    {

        $this->queue = array();

        $last_order = wc_ec_get_customer_last_order($this->id);

        $meta_types = array(
            'first_name',
            'last_name',
            'billing_company',
            'billing_address_1',
            'billing_postcode',
            'billing_city',
            'billing_phone',
            'billing_state',
            'billing_country'
        );

        $meta = $this->meta();

        foreach ($meta_types as $meta_type) {
            $this->queue[$meta_type] = $meta->get($meta_type);
        }

        $last_order_not_shipped = wc_get_customer_last_order($this->id);

        $this->queue['store_registration_date'] = wc_ec_get_user_registration($this->id);
        $this->queue['user_email'] = $this->getEmail();
        $this->queue['customer_id'] = $this->id;
        $this->queue['customer_type'] = 'Customer';
        $this->queue['currency'] = $this->getCurrency();
        $this->queue['total_ordered'] = $this->totalOrdered();
        $this->queue['total_ordered_30d'] = $this->totalOrdered(30);
        $this->queue['total_ordered_12m'] = $this->totalOrdered(365);
        $this->queue['total_orders'] = $this->totalOrders();

        $this->queue['all_ordered_product_ids'] = wc_ec_get_all_products($this->id);
        $this->queue['latest_order_product_ids'] = wc_ec_get_all_products($this->id, 1);

        $this->queue['latest_order_id'] = $last_order_not_shipped !== FALSE ? $last_order_not_shipped->get_id() : 0;
        $this->queue['latest_order_date'] = $last_order_not_shipped !== FALSE ? $last_order_not_shipped->get_date_modified()->date_i18n() : '';

        if ($this->queue['latest_order_id'] != null) {
            $this->queue['latest_order_amount'] = $last_order_not_shipped !== FALSE ? $last_order_not_shipped->get_total() : 0;
        } else {
            $this->queue['latest_order_amount'] = 0;
        }

        if ($last_order_not_shipped !== FALSE) {
            $this->queue['latest_order_status'] = wc_ec_get_order_status_name("wc-" . $last_order_not_shipped->get_status());
        }


        $this->queue['newsletter'] = $meta->get("wc_emailchef_opt_in");
        $this->queue['source'] = WCEC()->get_platform();

        $this->queue['all_ordered_product_ids'] = wc_ec_get_all_products($this->id);
        $this->queue['latest_order_product_ids'] = wc_ec_get_all_products($this->id, 1);

        $this->queue['latest_shipped_order_id'] = $last_order !== FALSE ? $last_order->get_id() : 0;
        $this->queue['latest_shipped_order_date'] = $last_order !== FALSE ? $last_order->get_date_modified()->date_i18n() : '';
        $this->queue['latest_shipped_order_status'] = wc_ec_get_order_status_name($last_order !== FALSE ? "wc-" . $last_order->get_status() : '');

        return $this->queue;

    }

    public function sync_user($newsletter = "no")
    {

        $this->queue = array();

        $meta_types = array(
            'first_name',
            'last_name',
            'billing_company',
            'billing_address_1',
            'billing_postcode',
            'billing_city',
            'billing_phone',
            'billing_state',
            'billing_country'
        );

        $meta = $this->meta();

        foreach ($meta_types as $meta_type) {
            $this->queue[$meta_type] = $meta->get($meta_type);
        }

        $this->queue['user_email'] = $this->getEmail();
        $this->queue['customer_type'] = 'Customer';
        $this->queue['customer_id'] = $this->id;
        $this->queue['store_registration_date'] = wc_ec_get_user_registration($this->id);
        $this->queue['currency'] = $this->getCurrency();
        $this->queue['newsletter'] = $newsletter;
        $this->queue['source'] = WCEC()->get_platform();

        return $this->queue;

    }


}
