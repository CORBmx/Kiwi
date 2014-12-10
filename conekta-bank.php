<?php

/**
 * Woocommerce payment gateway that handles the bank transactions
 */
class conektaWC_bank extends WC_Payment_Gateway{
    
    public $charge;
    public $conektaWC_options;
    
    /**
     * Sets the default settings that Woocommerce use to handle each payment gateway,
     * gets the necessary plugin specific options,
     * adds an action to display the default settings in the Woocommerce admin panel
     */
	function __construct(){
		$this->conektaWC_options = get_option('conektaWC_options');

		$this->id = 'conekta_bank';
		$this->order_button_text = 'Realizar el pedido';
		$this->icon = '';
		$this->has_fields = true;
		$this->method_title = __('Transferencia bancaria(Banorte)','conektaWC');
		$this->method_description = __('Pago mediante Transferencia bancaria(Banorte)','ConektaWC');

		$this->init_form_fields();
		$this->init_settings();
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}
    
    /**
     * initializes the settings fields that displays at the Woocommerce admin panel
     * @return none
     */
	function init_form_fields(){
		global $woocommerce;
		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'woocommerce' ),
				'type' => 'checkbox',
				'label' => __( 'Habilitar pago mediante transferencia bancaria(Banorte)', 'conektaWC' ),
				'default' => 'no'
			),
			'title' => array(
				'title' => __( 'Title', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default' => __( 'Transferencia bancaria(Banorte)', 'conektaWC' ),
				'desc_tip'      => true,
			),
			'description' => array(
				'title' => __( 'Description', 'woocommerce' ),
				'type' => 'textarea',
				'default' => __('Haz el pago en cualquier sucursal Banorte de tu preferencia','ConektaWC')
			)
		);
	}
	
	/**
	 * Sets the specific payment instructions 
	 */
    function set_email_instructions(){
        
        echo '<h2>' . __('Informacion de pago:', 'conektaWC') . '</h2>';
        echo '<p><strong>' . __('Use los siguientes datos para efectuar su pago en la sucursal bancaria Banorte que le convenga', 'conektaWC') . '</p>';
        echo '<p><strong>'. __('Banco: ', 'conektaWC') . '</strong>' . ucfirst($this->charge->payment_method->type) . '</p>';
        echo '<p><strong>'. __('Nombre del servicio: ', 'conektaWC') . '</strong>' . $this->charge->payment_method->service_name . '</p>';
        echo '<p><strong>'. __('Numero del servicio: ', 'conektaWC') . '</strong>' . $this->charge->payment_method->service_number . '</p>';
        echo '<p><strong>'. __('Nombre de referencia: ', 'conektaWC') . '</strong>' . $this->charge->payment_method->reference . '</p>';
        
    }
	
	/**
	 * [process_payment description]
	 * @param  integer $order_id
	 * @return array
	 */
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
		  'bank'            =>array('type'=>'banorte')
		));
        
        add_action( 'woocommerce_email_before_order_table', array( $this, 'set_email_instructions' ), 10, 2 );
        
		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status('on-hold', __( 'Awaiting cheque payment', 'woocommerce' ));

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