<?php

class conektaWC_oxxo extends WC_Payment_Gateway{
    
    public $charge;
    public $dateoxxo;
    public $conektaWC_options;
    
	function __construct(){
		$this->conektaWC_options = get_option('conektaWC_options');

		$this->id = 'conekta_oxxo';
		$this->order_button_text = 'Realizar el pedido';
		$this->icon = '';
		$this->has_fields = false;
		$this->method_title = 'Oxxo';
		$this->method_description = __('Pago por Oxxo','conektaWC');

		$this->init_form_fields();
		$this->init_settings();
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	function init_form_fields(){
		global $woocommerce;
		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'woocommerce' ),
				'type' => 'checkbox',
				'label' => __( 'Habilitar metodo de pago por Oxxo', 'conektaWC' ),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __( 'Title', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'conektaWC' ),
				'default' => __( 'Oxxo', 'woocommerce' ),
				'desc_tip'      => true,
			),
			'description' => array(
				'title' => __( 'Description', 'woocommerce' ),
				'type' => 'textarea',
				'default' => __('Pago por Oxxo', 'conektaWC')
			)
		);
		
	}
    
    function email_instructions(){
        $this->dateoxxo = $this->charge->payment_method->expiry_date;
        
        echo '<h2>' . __('Informacion de pago:', 'conektaWC') . '</h2>';
        echo '<p>' . __('Use el siguiente codigo para efectuar su pago en la sucursal Oxxo que le convenga', 'conektaWC') . '</p>';
        echo '<p>'. __('Fecha limite de pago: ', 'conektaWC') . substr($this->dateoxxo, 0, 2) . '/' . substr($this->dateoxxo, 2, 2) . '/' . substr($this->dateoxxo, 4, 2) . '</p>';
        echo '<img src="' . $this->charge->payment_method->barcode_url . '" width="242" height="50" border="0" alt="my picture" /><br>';
        echo $this->charge->payment_method->barcode;
        
    }
    
	function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );
        
        require_once( plugin_dir_path( __FILE__ ).'php/lib/Conekta.php' );
        
        Conekta::setApiKey( $this->conektaWC_options['conektaWC_private_key'] );        
        
		$this->charge = Conekta_Charge::create(array(
		  'description'		=>'Order from: ' . get_bloginfo(), 
		  'reference_id'	=>$order_id,
		  'amount'			=>$order->get_total() * 100,
		  'currency'		=>'MXN',
		  'cash'            =>array( 'type'=>'oxxo' )
		));
        
        add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 2 );
        
		// Mark as on-hold 
		$order->update_status('on-hold', __( 'Esperando que se efectue el pago en Oxxo', 'woocommerce' ));

		// Reduce stock levels
		$order->reduce_order_stock();

		// Remove cart
		$woocommerce->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}

}