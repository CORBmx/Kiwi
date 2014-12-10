<?php

class conektaWC_card extends WC_Payment_Gateway{
    
    public $charge;
    public $customer;
    public $clienteId;
    public $idusuario;
    public $conektaWC_options;
    public $default_fields;
    
    function __construct(){
        $this->conektaWC_options = get_option('conektaWC_options');
        
        $this->clienteId = get_user_meta(get_current_user_id(), 'conektaCustId', true);
        $this->clienteCard = get_user_meta(get_current_user_id(), 'conektaCustCard', true);
        $this->idusuario = get_current_user_id();
		$this->id = 'conekta_card';
		$this->order_button_text = 'Realizar el pedido';
		$this->icon = '';
        if( empty( $this->clienteId ) ){
          $this->has_fields = true;
        }
        else{
            $this->has_fields = false;
        }
        $this->method_title = __('Tarjeta de crédito/débito','conektaWC');
        $this->method_description = __('Pago con tarjeta de crédito/débito','conektaWC');

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
                'label' => __( 'Habilitar pago mediante tarjeta de credito/debito', 'conektaWC' ),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __( 'Title', 'woocommerce' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'default' => __('Tarjeta de credito/debito','conektaWC'),
                'desc_tip'      => true,
            ),
            'description' => array(
                'title' => __( 'Description', 'woocommerce' ),
                'type' => 'textarea',
                'default' => __('Pague con su ultima tarjeta de credito/debito registrada:', 'conektaWC')
            )
        );
    }

    function set_default_fields(){
        $this->default_fields = array(
         'card-number-field' => '<p class="form-row form-row-wide">
             <label for="' . esc_attr( $this->id ) . '-card-number">' . __( 'Card Number', 'woocommerce' ) . ' <span class="required">*</span></label>
             <input data-conekta="card[number]" id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number cardConekta" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="' . ( $args['fields_have_names'] ? $this->id . '-card-number' : '' ) . '" />
         </p>',
         'card-expiry-field' => '<p class="form-row form-row-first">
           <label for="' . esc_attr( $this->id ) . '-card-expiry">' . __( 'Expiry (MM/YY)', 'woocommerce' ) . ' <span class="required">*</span></label>
             <input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry cardConekta" type="text" autocomplete="off" placeholder="' . __( 'MM / YY', 'woocommerce' ) . '" name="' . ( $args['fields_have_names'] ? $this->id . '-card-expiry' : '' ) . '" />
         </p>',
         'card-cvc-field' => '<p class="form-row form-row-last">
             <label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( 'Card Code', 'woocommerce' ) . ' <span class="required">*</span></label>
            <input data-conekta="card[cvc]" id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc cardConekta" type="text" autocomplete="off" placeholder="' . __( 'CVC', 'woocommerce' ) . '" name="' . ( $args['fields_have_names'] ? $this->id . '-card-cvc' : '' ) . '" />
         </p>',
         'card-name'         => '<input type="hidden" name="card-name" data-conekta="card[name]"/>',
         'exp-month'         => '<input type="hidden" name="exp-month" data-conekta="card[exp_month]"/>',
         'exp-year'          => '<input type="hidden" name="exp-year" data-conekta="card[exp_year]"/>',
         'conektaTokenId'    => '<input type="hidden" name="conektaTokenId" value="" />',
         'conektaCustId'     => '<input type="hidden" name="conektaCustId" value="' . ( ( empty( $this->clienteId ) ) ? "true" : "false" ) . '" />',
         'pbkey'             => '<input type="hidden" name="pbkey" value="' . $this->conektaWC_options['conektaWC_public_key'] . '" />'
        );

        $this->credit_card_form($args, $this->default_fields);
    }

    function payment_fields(){

        $args = array( 'fields_have_names' => true );
        if( empty( $this->clienteId ) ){
            
            $this->set_default_fields();
            
        }
        else{
            echo wpautop( wptexturize( $this->description ) ) . "**** **** **** " . $this->clienteCard;
            ?>
                <p>
                    <label for="conekta-card-new-card"><input id="conekta-card-new-card" name="conekta-card-new-card" type="checkbox">utiliza otra tarjeta</label>
                </p>
                
                <div id="conekta-card-hidden">
                    <?php
                        $this->set_default_fields();
                    ?>
                <script type="text/javascript">
                    jQuery("input#conekta-card-new-card").change(function(){
                        jQuery("#conekta-card-hidden").slideToggle('fast');
                    });
                </script>
                </div>
            <?php
        }
                
    }
    
    function process_payment( $order_id ) {
        global $woocommerce;
        $order = new WC_Order( $order_id );
        
        require_once( plugin_dir_path( __FILE__ ).'php/lib/Conekta.php' );

        Conekta::setApiKey( $this->conektaWC_options['conektaWC_private_key'] );        
        
        if( empty( $this->clienteId ) || isset($_POST['conekta-card-new-card']) ){
            $this->customer = Conekta_Customer::create( array(
                "name"          => $_POST['billing_first_name'] . " " . $_POST['billing_last_name'],
                "email"         => $_POST['billing_email'],
                "phone"         => $_POST['billing_phone'],
                "cards"         =>array( $_POST['conektaTokenId'] )
            ));

            $this->charge = Conekta_Charge::create(array(
              'description'     =>'Order from: ' . get_bloginfo(), 
              'reference_id'    =>$order_id,
              'amount'          =>$order->get_total() * 100,
              'currency'        =>'MXN',
              'card'            => $this->customer->id
            ));
            
            if( $this->conektaWC_options['conektaWC_save_customer']!=NULL ){
                if( empty($this->clienteId) ){
                    add_user_meta( $this->idusuario, 'conektaCustId',$this->customer->id, true);
                    add_user_meta( $this->idusuario, 'conektaCustCard',$this->customer->cards[0]->last4, true);
                }
                else{
                    update_user_meta( $this->idusuario, 'conektaCustId',$this->customer->id);
                    update_user_meta( $this->idusuario, 'conektaCustCard',$this->customer->cards[0]->last4);
                }
            }
        }
        else{
            $this->charge = Conekta_Charge::create(array(
              'description'     =>'Order from: ' . get_bloginfo(), 
              'reference_id'    =>$order_id,
              'amount'          =>$order->get_total() * 100,
              'currency'        =>'MXN',
              'card'            => $this->clienteId
            ));
        }
        
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