<?php
/**
 * @author: Einar Óli
 */
if (!defined('AREA')) { die('Access denied'); }

function cleanAmount($amount) {
	return (int)preg_replace('/[^\d]/', '', $amount);
}

/**
 * Payment request comes back
 */
if (defined('PAYMENT_NOTIFICATION'))
{
	$pp_response    = array();
	$order_id       = intval($_REQUEST['order_id']);
	$order_info     = fn_get_order_info($order_id);
	$payment_id     = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id=?i", $order_id);
	$processor_data = fn_get_processor_data($payment_id);


	if (fn_check_payment_script('netgiro.php', $order_id, $processor_data))
	{
		if ($mode == 'success')
		{
			$pp_response['order_status']   = 'P';
			$pp_response['reason_text']    = fn_get_lang_var('transaction_approved');
			$pp_response['transaction_id'] = $_REQUEST['order_id'];
			fn_finish_payment($order_id, $pp_response);
			fn_order_placement_routines($order_id);
			exit;
		}
		else if ($mode == 'cancel')
		{
			$pp_response['order_status']   = 'F';
			$pp_response['reason_text']    = fn_get_lang_var('transaction_declined');
			$pp_response['transaction_id'] = $_REQUEST['order_id'];
			fn_order_placement_routines($order_id);
			exit;
		}
	}
}
/**
 * The data to send over
 */
else
{
	$gateway = ($processor_data['params']['mode'] == 'test')
		// TEST url
		? 'http://test.netgiro.is/user/securepay'
		// LIVE url
		: 'https://securepay.netgiro.is/v1';

	$currencies   = Registry::get('currencies');
	$currencyCode = key($currencies);
	$currency     = $currencies['ISK'];

	$total = fn_format_rate_value($order_info['total'], 'F', $currency['decimals'], '.', '', $currency['coefficient']);

	$msg = fn_get_lang_var('text_cc_processor_connection');
	$msg = str_replace('[processor]', 'Netgíró', $msg);

	$post = array();

	$post['ApplicationID']        = $processor_data['params']['netgiro_application_id'];
	$post['IframeID']             = 'false';
	$post['PaymentSuccessfulURL'] = Registry::get('config.current_location') . "/$index_script?dispatch=payment_notification.success&payment=netgiro&order_id=$order_id";
	$post['PaymentCancelledURL']  = Registry::get('config.current_location') . "/$index_script?dispatch=payment_notification.cancel&payment=netgiro&order_id=$order_id";
	$post['ReturnCustomerInfo']   = "true";
	$post['OrderId']              = $order_id;
	$post['TotalAmount']          = $total;
	$post['Signature'] 			  = hash('sha256', $processor_data['params']['netgiro_secret_key'] . $post['OrderId'] . $post['TotalAmount'] . $post['ApplicationID']);

	/**
	 * Discpunt amount
	 */
	$discount = '';
	if ($order_info['subtotal_discount'] != 0)
	{
		$discount_amount = cleanAmount(fn_format_rate_value($order_info['subtotal_discount'], 'F', $currency['decimals'], '.', '', $currency['coefficient']));
		$discount        = '<input type="hidden" name="DiscountAmount" value="' . $discount_amount . '" />';
	}


	/**
	 * Shipping amount
	 */
	$shipping = '';
	if ($order_info['shipping_cost'] != 0)
	{
		$shipping_amount = cleanAmount(fn_format_rate_value($order_info['shipping_cost'], 'F', $currency['decimals'], '.', '', $currency['coefficient']));
		$shipping        = '<input type="hidden" name="ShippingAmount" value="' . $shipping_amount . '" />';
	}


	/**
	 * Make products list
	 */
	$items = '';
	$i     = 0;
	foreach ($order_info['items'] as $product)
	{
		$Name      = htmlspecialchars(strip_tags($product['product']));
		$UnitPrice = cleanAmount(fn_format_rate_value(($product['subtotal'] / $product['amount']), 'F',	$currency['decimals'], '.',	'',	$currency['coefficient']));
		$ProductSubtotal = cleanAmount(fn_format_rate_value($product['subtotal'], 'F', $currency['decimals'], '.', '', $currency['coefficient']));

		$items .= '<input type="hidden" name="Items[' . $i . '].ProductNo" value="' . $product['product_code'] . '" />';
		$items .= '<input type="hidden" name="Items[' . $i . '].Name" value="' . $Name . '" />';
		// $items .= '<input type="hidden" name="Items[' . $i . '].Description" value="" />';
		$items .= '<input type="hidden" name="Items[' . $i . '].UnitPrice" value="' . $UnitPrice . '" />';
		$items .= '<input type="hidden" name="Items[' . $i . '].Amount" value="' . $ProductSubtotal . '" />';
		$items .= '<input type="hidden" name="Items[' . $i . '].Quantity" value="' . ($product['amount'] * 1000) . '" />';

		$i++;
	}

	echo <<<EOT
	<html>
	<body onLoad="document.process.submit();">
		<form action="{$gateway}" method="POST" name="process">
			<input type="hidden" name="ApplicationID" value="{$post['ApplicationID']}" />
			<input type="hidden" name="IframeID" value="{$post['IframeID']}" />
			<input type="hidden" name="PaymentSuccessfulURL" value="{$post['PaymentSuccessfulURL']}" />
			<input type="hidden" name="PaymentCancelledURL" value="{$post['PaymentCancelledURL']}" />
			<input type="hidden" name="ReturnCustomerInfo" value="{$post['ReturnCustomerInfo']}" />
			<input type="hidden" name="OrderId" value="{$post['OrderId']}" />
			<input type="hidden" name="Signature" value="{$post['Signature']}" />
			<input type="hidden" name="TotalAmount" value="{$post['TotalAmount']}" />
			$items
			$discount
			$shipping
		</form>
		<p>
		<div align=center>{$msg}</div>
		</p>
	</body>
	</html>
EOT;

}

exit;

?>
