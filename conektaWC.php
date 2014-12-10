<?php

/*
	Plugin name: Conekta.io WooCommerce Payment Gateway
	Plugin URI: corb.mx
	Descripcion: Plugin for adding the conekta service to payment gateways in the WooCommerce plugin
	Version: 0.1
	Author:Cesar Landeros
	Author URI: corb.mx
*/

class conektaWC {
    
    public $options, $current_page;
    
    /**
     * [__construct description]
     */
    public function __construct(){
        $this->options = get_option('conektaWC_options');
        

        add_action('admin_menu', array( $this, 'add_to_admin_menu') );
        add_action('admin_init', array( $this, 'register_conekta_settings') );
    
        add_action( 'admin_notices', array($this, 'plugin_key_notice') );
        
        if ( get_option("is_conektaWC_plugin_key_activated") ) {
            
            add_action( 'wp_enqueue_scripts', array( $this, 'conektaWC_scripts_enqueue') );
            add_action( 'plugins_loaded', array( $this, 'conektaWC_class_require') );

            add_filter('woocommerce_payment_gateways', array( $this, 'get_conektaWC_methods') );
            
        } elseif( $_GET['page'] === "conekta_options" ){ // esta condicional solo es provicional, debera eliminarse y sustituir la url por la de produccion
            
            $curl_request = curl_init();

            curl_setopt_array(
                $curl_request, 
                array(
                    CURLOPT_URL => 'http://devel.corb.mx/nafarrate/',
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => 'wc-api=corb&key=' . $this->options['conektaWC_plugin_key'] . "&name=conekta.io&website=" . get_site_url(),
                    CURLOPT_RETURNTRANSFER => true
                )
            );

            $response = curl_exec( $curl_request );
            curl_close( $curl_request );
            if ( $response == "true" ) {
                echo "string";
                add_option("is_conektaWC_plugin_key_activated", true);
                update_option("is_conektaWC_plugin_key_activated", true);

            }          
        
        }
        
    }
    
    /**
     * [add_to_admin_menu description]
     */
    function add_to_admin_menu(){
        global $submenu;
		add_menu_page( "Conekta options", "Conekta", "manage_options", 'conekta_options', array( $this, 'conekta_options') , '' , 56);
	}

    /**
     * [conekta_options description]
     * @return [type] [description]
     */
    function conekta_options(){ 
        ?>
            <div class="wrap">
                <h2><?php echo __('Conekta gateway para Woocommerce','conektaWC'); ?></h2>
                <form method="post" action="options.php">
                    <?php settings_fields('conektaWC_options'); ?>
                    <?php do_settings_sections('conekta_options'); ?>
                    <p class="submit">
                        <input name="submit" type="submit" class="button-primary" value="<?php echo __('Guardar') ?>" />
                    </p>
                </form>
            </div>
        <?php 
    }
    
    /**
     * [register_conekta_settings description]
     * @return [type] [description]
     */
    function register_conekta_settings(){
        
        add_settings_section('conekta_options_main',__('Opciones para el gateway de Conekta.io'), array( $this, 'conekta_options_main_cb'),'conekta_options');
        
        add_settings_field('conektaWC_plugin_key', '<label for="conektaWC_options[conektaWC_plugin_key]">' . __('Key de instalacion', 'ConektaWC') . '</label>', array( $this, 'conekta_options_pluginkey_cb'), 'conekta_options', 'conekta_options_main');
        add_settings_field('conektaWC_private_key','<label for="conektaWC_options[conektaWC_private_key]">'. __('Llave privada', 'ConektaWC') . '</label>', array( $this, 'conekta_options_pvk_cb'), 'conekta_options', 'conekta_options_main');
        add_settings_field('conektaWC_public_key', '<label for="conektaWC_options[conektaWC_public_key]">' . __('Llave pública', 'ConektaWC') . '</label>', array( $this, 'conekta_options_pk_cb'), 'conekta_options', 'conekta_options_main');
        add_settings_field('conektaWC_save_customer', __('Guardar id del ciente'), array( $this, 'conekta_options_sc_cb'), 'conekta_options', 'conekta_options_main');
        
        register_setting('conektaWC_options', 'conektaWC_options', array( $this, 'conekta_settings_validation'));
    }
    
    /**
     * [conekta_options_main_cb description]
     * @return [type] [description]
     */
    function conekta_options_main_cb(){
        //opcional
    }
    
    function conekta_options_pluginkey_cb(){
        echo "<input id='conektaWC_options[conektaWC_plugin_key]' name='conektaWC_options[conektaWC_plugin_key]' type='text' value='" . $this->options['conektaWC_plugin_key'] ."'/>";
    }

    /**
     * [conekta_options_pk_cb description]
     */
    function conekta_options_pk_cb(){
        echo "<input id='conektaWC_options[conektaWC_public_key]' name='conektaWC_options[conektaWC_public_key]' type='text' value='" . $this->options['conektaWC_public_key'] ."'/>";
    }
    
    /**
     * [conekta_options_pvk_cb description]
     */
    function conekta_options_pvk_cb(){
        echo "<input id='conektaWC_options[conektaWC_private_key]' name='conektaWC_options[conektaWC_private_key]' type='text' value='" . $this->options['conektaWC_private_key'] ."'/>";
    }
    
    /**
     * [conekta_options_sc_cb description]
     */
    function conekta_options_sc_cb(){
        echo "<label for='conektaWC_options[conektaWC_save_customer]'><input id='conektaWC_options[conektaWC_save_customer]' name='conektaWC_options[conektaWC_save_customer]' " . ( ($this->options['conektaWC_save_customer'] == NULL) ? "" : "checked='checked'") . "type='checkbox' value='on'/>Se utiliza para futuras compras, asi el cliente no ingresa su tajeta mas de 1 vez</label>";
        
    }

    function conekta_settings_validation( $input ){

         // Create our array for storing the validated options
        $output = array();
         
        // Loop through each of the incoming options
        foreach( $input as $key => $value ) {
            
            // Check to see if the current option has a value. If so, process it.
            if( isset( $input[$key] ) && ( substr( $value, 0, 4) === "key_" || ( $key === "conektaWC_save_customer" && $value === "on" ) || $key === "conektaWC_plugin_key" ) ) {
                
                // Strip all HTML and PHP tags and properly handle quoted strings
                $output[$key] = strip_tags( stripslashes( $input[ $key ] ) );

                 
            }
             
        } // end foreach
         
        // Return the array processing any additional functions filtered by this action
        return apply_filters( 'conekta_settings_validation', $output, $input );
    }

    function plugin_key_notice(){
        
        $licence_key = get_option('conektaWC_options');
        $this->current_page = get_current_screen();        

        if( $this->current_page->parent_base === "conekta_options" ){
            if ( $licence_key['conektaWC_plugin_key'] === NULL ) {
                ?>
                <div class="updated">
                    <p><?php _e( 'Gracias por intalar el plugin para Conekta.io de Corb.mx, porfavor, tomate un tiempo y activa el plugin antes de poder utilizarlo.', 'ConektaWC' ); ?></p>
                </div>
                <?php
            }

            if ( !get_option("is_conektaWC_plugin_key_activated") ) {
                
                ?>
                <div class="error">
                    <p><?php _e( 'La licencia es incorrecta o no se ingreso correctamente', 'ConektaWC' ); ?></p>
                </div>
                <?php

            }
        }
        
    }

    function activated_plugin_notice(){

        ?>
        <div class="error">
            <p><?php _e( 'La licencia es incorrecta o no se ingreso correctamente', 'ConektaWC' ); ?></p>
        </div>
        <?php

    }
    
    /**
     * [conektaWC_scripts_enqueue description]
     */
    function conektaWC_scripts_enqueue(){
		wp_enqueue_script( 'jquery');
		wp_enqueue_script( 'conektaJS', 'https://conektaapi.s3.amazonaws.com/v0.3.1/js/conekta.js');
		wp_enqueue_script( 'conektaWCJS', plugins_url('js/conektaWC.js' , __FILE__));
	}

    /**
     * [conektaWC_class_require description]
     */
    function conektaWC_class_require(){
		require_once( plugin_dir_path( __FILE__ ).'conekta-bank.php' );
		require_once( plugin_dir_path( __FILE__ ).'conekta-oxxo.php' );
		require_once( plugin_dir_path( __FILE__ ).'conekta-card.php' );
	}
    
    /**
     * [get_conektaWC_methods description]
     * @return array
     */
    function get_conektaWC_methods(){
        $methods[] = 'conektaWC_card';
		$methods[] = 'conektaWC_oxxo';
		$methods[] = 'conektaWC_bank';
        $methods[] = 'WC_Gateway_BACS';
        $methods[] = 'WC_Gateway_Paypal';
		return $methods;
	}
    
}

function install_conekta(){

    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'activ e_plugins', get_option( 'active_plugins' ) ) ) ) {
        
    } else {
        die("Woocommerce must be activated");
    }

}

$conektaWC = new conektaWC();
register_activation_hook( __FILE__, 'install_conekta' );
