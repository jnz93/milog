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

                private $requestService;
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
                    $this->requestService       = new Milog_Request_Service();

                    # Disponibilidade em países
                    $this->availability         = 'including';
                    $this->countries            = array(
                        'BR', # Brasil
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
                    global $WCFM;
                    $weight     = 0;
                    $cost       = 0;

                    # Package data
                    $_content       = $package['contents'];
                    $_storeId       = $package['vendor_id'];
                    $_storeData     = wcfmmp_get_store( $_storeId );
                    $_storeInfo     = $_storeData->get_shop_info();
                    $_storeZipCode  = $_storeInfo['address']['zip'];
                    $_destZipCode   = $package['destination']['postcode'];

                    # Settings Request params
                    $_route             = '/shipment/calculate';
                    $_typeRequest       = 'POST';
                    $_body              = array();
                    $_body['from']      = [ 'postal_code' => $_storeZipCode ];
                    $_body['to']        = [ 'postal_code' => $_destZipCode ];
                    $_body['products']  = array();

                    foreach( $_content as $item => $values ) {
                        $_id        = $values['product_id'];                        
                        $_product   = $values['data'];
                        $_quantity  = $values['quantity'];
                        $_name      = $_product->get_name();
                        $_price     = $_product->get_price();
                        $_weight    = $_product->get_weight();
                        $_width     = $_product->get_width();
                        $_height    = $_product->get_height();
                        $_length    = $_product->get_length();

                        $_body['products'][] = [
                            'id'                => $_name,      # Title/Name
                            'width'             => $_width,     # post_meta: width
                            'height'            => $_height,    # post_meta: height
                            'length'            => $_length,    # post_meta: length
                            'weight'            => $_weight,    # post_meta: weight
                            'insurance_value'   => 0,           # post_meta: valor de seguro
                            'quantity'          => $_quantity   # data: quantity
                        ];
                        $weight = $weight + $_product->get_weight() * $values['quantity'];
                    }

                    $_request = $this->requestService->request( $_route, $_typeRequest, $_body );
                    if( !empty( $_request ) ) {
                        foreach( $_request as $item => $data ) {

                            # Se a transportadora retornou erro pula para o próximo loop
                            if( isset( $data->error ) ) continue;

                            $_serviceId         = $data->id;
                            $_serviceName       = $data->name;
                            $_servicePrice      = $data->custom_price;
                            $_serviceCurrency   = $data->currency;
                            $_deliveryTime      = $data->delivery_time;
                            $_companyId         = $data->company->id;
                            $_companyName       = $data->company->name;
                            $_companyThumb      = $data->company->picture;

                            $rate = array(
                                'id'    => $this->id . '-' . $_serviceName,
                                'label' => $_serviceName,
                                'cost'  => $_servicePrice
                            );
                            $this->add_rate( $rate );
                        }
                    }
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