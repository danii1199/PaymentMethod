<?php

/**
 * Class WC_Gateway_Paypal_Request file.
 *
 * @package WooCommerce\Gateways
 */


if (!defined('ABSPATH')) {
	exit;
}

/**
 * Generates requests to send to PayPal.
 */
class WC_Gateway_Sepalo_Request
{

	/**
	 * Stores line items to send to PayPal.
	 *
	 * @var array
	 */
	protected $line_items = array();

	/**
	 * Pointer to gateway making the request.
	 *
	 * @var WC_Gateway_Sepalo
	 */
	protected $gateway;

	/**
	 * Endpoint for requests to PayPal.
	 *
	 * @var string
	 */
	protected $endpoint;


	/**
	 * Constructor.
	 *
	 * @param WC_Gateway_Paypal $gateway Paypal gateway object.
	 */
	public function __construct($gateway)
	{
		$this->gateway    = $gateway;
		$this->notify_url = WC()->api_request_url('WC_Gateway_Sepalo');
	}

	/**
	 * Get the PayPal request URL for an order.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  bool     $sandbox Whether to use sandbox mode or not.
	 * @return string
	 */
	public function get_request_url($order)
	{

		$query_args = array(
			'id_user' => $order->get_user_id(),
			'first_name' => $order->get_billing_first_name(),
			'last_name' => $order->get_billing_last_name(),
			'company' => $order->get_billing_company(),
			'country' => $order->get_billing_country(),
			'address1' => $order->get_billing_address_1(),
			'address2' => $order->get_billing_address_2(),
			'post_code' => $order->get_billing_postcode(),
			'city' => $order->get_billing_city(),
			'state' => $order->get_billing_state(),
			'phone' => $order->get_billing_phone(),
			'email' => $order->get_billing_email(),
			'amount' => $order->get_total(),
			'id_order' => $order->get_id(),
			'id_client' => $this->gateway->get_option('id_client'),
			'secret_key' => $this->gateway->get_option('secret_key_client'),
			'order_key' => $order->get_order_key()
		);

		$return_url = 'https://des.datta.capital/pago-con-sepalo';
		$args = http_build_query($query_args);

		$arg_serialize = serialize($query_args);
		$arg = base64_encode($arg_serialize);

		return $return_url . '?' . $arg;
	}
}
