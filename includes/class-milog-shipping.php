<?php
/**
 * Provide the shipping custom class
 * That class extends Woocommerce 
 * *
 * @link       unitycode.tech
 * @since      1.0.0
 *
 * @package    Milog
 * @subpackage Milog/includes
*/

if( !defined( 'WPINC') ) die;

/**
 * Check if Woocommerce is active
 */
if( in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    function milog_shipping_method()
    {
        if( !class_exists( 'Milog_Shipping_Method' ) ) {
            class Milog_Shipping_Method extends WC_Shipping_Method
            {

                /**
                 * Constructor for shipping class
                 * 
                 * @access public
                 * @return void
                 */
                public function __construct()
                {
                    $this->id                   = 'milog';
                    $this->method_title         = __( 'Mercado Indústria Envios', 'milog' );
                    $this->method_description   = __( 'Cotação de fretes para marketplace consumindo API Melhor Envio', 'milog' );
                    
                    # Disponibilidade em países
                    $this->availability         = 'including';
                    $this->countries            = array(
                        'BR', # Brasil
                        'US', # United States of America
                        'DE', # Germany
                        'IT', # Italy
                    );                    
                    $this->init();

                    $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                    $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Mercado Indústria Envios', 'milog' );
                }

                /**
                 * Init settings
                 * 
                 * @access public
                 * @return void
                 */
                function init()
                {
                    $this->init_form_fields();
                    $this->init_settings();

                    # Save settings in admin if have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }


                /**
                 * Define settings field for this shipping
                 * 
                 * @return void
                 */
                function init_form_fields()
                {
                    # Adicionar configurações do método aqui
                    $this->form_fields = array(
                        'enabled'           => array(
                            'title'         => __( 'Habilitar', 'milog' ),
                            'type'          => 'checkbox',
                            'description'   => __( 'Habilitar Cotação Melhor Envio para Marketplace', 'milog' ),
                            'default'       => 'yes'
                        ),
                        'title'             => array(
                            'title'         => __( 'Title', 'milog' ),
                            'type'          => 'text',
                            'description'   => __( 'Descrição no site', 'milog' ),
                            'default'       => __( 'Calcular Entrega', 'milog' ),
                        ),
                        'weight'            => array(
                            'title'         => __('Peso (kg)', 'milog' ),
                            'type'          => 'number',
                            'description'   => __('Peso máximo suportado', 'milog' ),
                            'default'       => 100
                        ),
                    );
                }

                /**
                 * This function is used to calculate the shipping cost. Within this function, we can check for weights, dimensions, and other parameters
                 * 
                 * @param mixed $package
                 * @access public
                 * @return void
                 */
                public function calculate_shipping( $package )
                {
                    # adicionar toda lógica da cotação aqui;
                    $weight = 0;
                    $cost   = 0;
                    $country = $package['destination']['country'];

                    foreach( $package['contents'] as $item_id => $values ) {
                        $_product = $values['data'];
                        $weight = $weight + $_product->get_weight() * $values['quantity'];
                    }
                    $weight = wc_get_weight( $weight, 'kg' );

                    if( $weight <= 10 ) {
                        $cost = 5;
                    } 
                    elseif( $weight <= 50 ) {
                        $cost = 10;
                    }
                    else {
                        $cost = 20;
                    }

                    $countryZones = array(
                        'BR'    => 0, # Brasil
                        'US'    => 3, # United States of America
                        'DE'    => 1, # Germany
                        'IT'    => 1, # Italy
                    );
                    
                    $zonePrices = array(
                        0   => 10,
                        1   => 30,
                        2   => 50,
                        3   => 70
                    );

                    $zoneFromCountry = $countryZones[$country];
                    $priceFromZone = $zonePrices[$zoneFromCountry];

                    $cost += $priceFromZone;

                    $rate = array(
                        'id'    => $this->id,
                        'label' => $this->title,
                        'cost'  => $cost
                    );
                    $this->add_rate( $rate );
                    // echo '<pre id="debug" style="display: none;">';
                    // print_r($package);
                    // echo '</pre>';
                }
            }
        }
    }

    add_action( 'woocommerce_shipping_init', 'milog_shipping_method' );

    function add_milog_shipping_method( $methods )
    {
        $methods[] = 'Milog_Shipping_Method';
        
        return $methods;
    }
    add_filter( 'woocommerce_shipping_methods', 'add_milog_shipping_method' );
}