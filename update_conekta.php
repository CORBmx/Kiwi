<?php

ob_start();
session_start();

require_once( '../../../wp-load.php' );

wp();
header('HTTP/1.1 200 OK');
if (!function_exists('wp_handle_upload')){
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
}

$body = @file_get_contents('php://input');
$event_json = json_decode($body);

if( $event_json->data->object->status == "paid" ){
	//if paid
	$new_status = get_term_by( 'slug', 'completed', 'shop_order_status' );

	wp_set_object_terms( $event_json->data->object->reference_id, array( $new_status->slug), 'shop_order_status', false );
}

file_put_contents("conekta_last_event.json", $event_json->data->object->id);