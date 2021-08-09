<?php

function get_order_status_delivered()
{

    return array(
        array('text' => 'Completed'),
    );

}

function get_order_statuses()
{

    return array(
        array("text" => "Pending Payment"),
        array("text" => "Processing"),
        array("text" => "On Hold"),
        array("text" => "Completed"),
        array("text" => "Cancelled"),
        array("text" => "Refunded"),
        array("text" => "Failed"),
    );

}

return array(

    'first_name'                  => array(
        'name' => __('Name', 'emailchef-for-woocommerce'),
        'type' => 'predefined',
    ),
    'last_name'                   => array(
        'name' => __('Surname', 'emailchef-for-woocommerce'),
        'type' => 'predefined',
    ),
    'user_email'                  => array(
        'name' => __('Email address', 'emailchef-for-woocommerce'),
        'type' => 'predefined',
    ),
    'source'                      => array(
        'name'          => __('Source', 'emailchef-for-woocommerce'),
        'type'          => 'text',
        'default_value' => WCEC()->get_platform(),
    ),
    'billing_company'             => array(
        'name' => __('Company', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'billing_address_1'           => array(
        'name' => __('Address', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'billing_postcode'            => array(
        'name' => __('ZIP code', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'billing_city'                => array(
        'name' => __('City', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'billing_phone'               => array(
        'name' => __('Phone', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'billing_state'               => array(
        'name' => __('State', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'billing_country'             => array(
        'name' => __('Country', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'newsletter'                  => array(
        'name'          => __('Agreed to newsletter', 'emailchef-for-woocommerce'),
        'type'          => 'select',
        'options'       => array(
            array(
                'text' => 'yes',
            ),
            array(
                'text' => 'no',
            ),
            array(
                'text' => 'pending',
            ),
        ),
        'default_value' => 'no',
    ),
    'currency'                    => array(
        'name' => __('Currency', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'customer_id'                 => array(
        'name' => __('Customer ID', 'emailchef-for-woocommerce'),
        'type' => 'number',
    ),
    'customer_type'               => array(
        'name'    => __('Customer type', 'emailchef-for-woocommerce'),
        'type'    => 'select',
        'options' => array(
            array(
                'text' => 'Customer',
            ),
            array(
                'text' => 'Guest',
            ),
        ),
    ),
    'total_ordered'               => array(
        'name' => __('Subtotal', 'emailchef-for-woocommerce'),
        'type' => 'number',
    ),
    'total_ordered_30d'           => array(
        'name' => __('Total ordered in the last 30 days', 'emailchef-for-woocommerce'),
        'type' => 'number',
    ),
    'total_ordered_12m'           => array(
        'name' => __('Total ordered in the last 12 months', 'emailchef-for-woocommerce'),
        'type' => 'number',
    ),
    'total_orders'                => array(
        'name' => __('Orders', 'emailchef-for-woocommerce'),
        'type' => 'number',
    ),
    'all_ordered_product_ids'     => array(
        'name' => __('Ordered Product ID', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'latest_order_id'             => array(
        'name' => __('Last order - ID', 'emailchef-for-woocommerce'),
        'type' => 'number',
    ),
    'latest_order_date'           => array(
        'name' => __('Last order - Date', 'emailchef-for-woocommerce'),
        'type' => 'date',
    ),
    'latest_order_amount'         => array(
        'name' => __('Last order - Total', 'emailchef-for-woocommerce'),
        'type' => 'number',
    ),
    'latest_order_status'         => array(
        'name'    => __('Last order - Status', 'emailchef-for-woocommerce'),
        'type'    => 'select',
        'options' => get_order_statuses(),
    ),
    'latest_order_product_ids'    => array(
        'name' => __('Last order - Product ID', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'latest_shipped_order_id'     => array(
        'name' => __('Last shipped order - ID', 'emailchef-for-woocommerce'),
        'type' => 'number',
    ),
    'latest_shipped_order_date'   => array(
        'name' => __('Last shipped order - Data', 'emailchef-for-woocommerce'),
        'type' => 'date',
    ),
    'latest_shipped_order_status' => array(
        'name'    => __('Last shipped order - Status', 'emailchef-for-woocommerce'),
        'type'    => 'select',
        'options' => get_order_status_delivered(),
    ),
    'ab_cart_is_abandoned_cart'   => array(
        'name' => __('Abandoned cart - Yes/No', 'emailchef-for-woocommerce'),
        'type' => 'boolean',
    ),
    'ab_cart_prod_name_pr_hr'     => array(
        'name' => __('Abandoned cart - Most expensive product name', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'ab_cart_prod_desc_pr_hr'     => array(
        'name' => __('Abandoned cart - Most expensive product description', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'ab_cart_prod_pr_pr_hr'       => array(
        'name' => __('Abandoned cart - Most expensive pricing product', 'emailchef-for-woocommerce'),
        'type' => 'number',
    ),
    'ab_cart_prod_url_pr_hr'      => array(
        'name' => __('Abandoned cart - Most expensive product URL', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'ab_cart_prod_url_img_pr_hr'  => array(
        'name' => __('Abandoned cart - Most expensive product image URL', 'emailchef-for-woocommerce'),
        'type' => 'text',
    ),
    'ab_cart_prod_id_pr_hr'       => array(
        'name' => __('Abandoned cart - Most expensive product ID', 'emailchef-for-woocommerce'),
        'type' => 'number',
    ),
    'ab_cart_date'                => array(
        'name' => __('Abandoned cart - Date', 'emailchef-for-woocommerce'),
        'type' => 'date',
    ),
    'store_registration_date'                => array(
        'name' => __('Store Registration - Date', 'emailchef-for-woocommerce'),
        'type' => 'date',
    )

);
