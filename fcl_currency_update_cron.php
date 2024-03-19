<?php
/*
 * Plugin Name:       Fcl Currency Value Update
 * Plugin URI:        https://lankashopstg.wpengine.com/
 * Description:       Update currency value AUD to LKR for every day.
 * Version:           1.0.0
 * Author:            Fcl
 * Author URI:        https://www.fclanka.com//
 * Domain Path:       /languages
 */



register_activation_hook(__FILE__, 'currency_converter_schedule');
add_action('currency_conversion_event', 'update_currency_value');

function currency_converter_schedule()
{
    if (!wp_next_scheduled('currency_conversion_event')) {
        wp_schedule_event(time(), 'every_minute', 'currency_conversion_event');
    }
}


function update_currency_value() {
    global $wpdb;

    $api_url = 'https://api.currencybeacon.com/v1?api_key=K7myY5zSPytIfosiqW73YqV0WVq0CgC5&base=AUD&symbols=LKR';
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        error_log('Error retrieving currency data: ' . $response->get_error_message());
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if (!$data || !isset($data->meta->code) || $data->meta->code !== 200 || !isset($data->rates->LKR)) {
        error_log('Error parsing currency data: Unexpected response format');
        return;
    }

    $lkr_value = number_format((float)$data->rates->LKR, 5, '.', '');
    $option_name = 'woo_multi_currency_params';
    $current_value = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM wp_options WHERE option_name = %s", $option_name));

    if (!$current_value) {
        error_log('Error retrieving current option value from the database');
        return;
    }

    $decoded_value = unserialize($current_value);

    if (!$decoded_value || !is_array($decoded_value)) {
        error_log('Error decoding current option value: Unexpected format');
        return;
    }

    $decoded_value['currency_rate'][0] = $lkr_value;
    $updated_value = serialize($decoded_value);

    $result = $wpdb->update('wp_options', array('option_value' => $updated_value), array('option_name' => $option_name));

    if ($result === false) {
        error_log('Error updating option value in the database');
        return;
    }

    error_log('Currency rate updated successfully');
}





