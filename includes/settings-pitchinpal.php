<?php
/**
 * Settings for PitchinPal™ Gateway
 */
return array(
	'enabled' => array(
		'title'   => __( 'Enable/Disable', 'woocommerce' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable PitchinPal™', 'woocommerce' ),
		'default' => 'yes'
	),
	'title' => array(
		'title'       => __( 'Title', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( 'PitchinPal™', 'woocommerce' ),
		'desc_tip'    => true,
	),
	'pitchinpal_url' => array(
		'title'       => __( 'What is PitchinPal™ link', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Video link about PitchinPal™.', 'woocommerce' ),
		'default'     => 'https://vimeo.com/142940582',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce' )
	),
	'description' => array(
		'title'       => __( 'Description', 'woocommerce' ),
		'type'        => 'text',
		'desc_tip'    => true,
		'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( 'PitchinPal™ is a great way to FriendFund your shopping cart.  Fund the whole amount or even a portion with your PitchinPal™ FriendFunds™.', 'woocommerce' )
	),
	'debug' => array(
		'title'       => __( 'Debug Log', 'woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable logging', 'woocommerce' ),
		'default'     => 'no',
		'description' => sprintf( __( 'Log PitchinPal™ events, such as IPN requests, inside <code>%s</code>', 'woocommerce' ), wc_get_log_file_path( 'pitchinpal' ) )
	),
	'store_identifier' => array(
		'title'       => __( 'Store Identifier', 'woocommerce' ),
		'description' => __( 'Get your API credentials from PitchinPal™.', 'woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'Required', 'woocommerce' )
	)
);
