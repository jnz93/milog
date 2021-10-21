<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       unitycode.tech
 * @since      1.0.0
 *
 * @package    Milog
 * @subpackage Milog/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Milog
 * @subpackage Milog/admin
 * @author     jnz93 <box@unitycode.tech>
 */
class Milog_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Classes
	 */
	private $requestService;
	private $ticketService;
	private $helpers;

	/**
	 * Routes
	 */
	private $routeCart;

	/**
	 * Types of request
	 */
	private $typeRequestPost;
	private $typeRequestGet;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( ) {

		$this->plugin_name 		= $plugin_name;
		$this->version 			= $version;
		
		$this->requestService	= new Milog_Request_Service();
		$this->ticketService 	= new Milog_Ticket();
		$this->helpers 			= new Milog_Helpers();

		$this->typeRequestPost  = 'POST';
		$this->typeRequestGet	= 'GET';
		$this->routeCart        = '/cart';

		/**
		 * Actions & Filters
		 */
		add_action( 'woocommerce_order_status_changed', array( $this, 'when_order_is_completed' ), 99, 4 );

		add_action( 'woocommerce_checkout_order_processed', array( $this, 'action_checkout_order_processed' ), 10, 3 );

		/**
		 * Shortcodes
		 */
		add_shortcode( 'milogAuth', array( $this, 'milogAuthMelhorEnvio' ) );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Milog_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Milog_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/milog-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Milog_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Milog_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/milog-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Metódo responsável por enviar as cotações de frete do pedido para o carrinho melhor envio;
	 * Este método é chamado no action woocommerce_order_status_changed
	 * @link https://woocommerce.github.io/code-reference/hooks/hooks.html
	 * 
	 * @param integer $order_id
	 * @param string $old_status
	 * @param string $new_status
	 * @param object $order
	 * 
	 * @since v1.0.0
	 */
	public function when_order_is_completed( $order_id, $old_status, $new_status, $order )
	{
		if( $new_status != 'completed' ) return;

		$freightsToCart = $this->ticketService->sanitize_freights_to_cart( $order_id );

		if( !empty( $freightsToCart ) ){
			$ticketsInCart = array();
			foreach( $freightsToCart as $storeFreight => $data ){
				
				/**
				 * Identificar se a transportadora é os correios
				 * Identificar se o volume é maior que 1
				 */
				if( $data['company'] == 'Correios' && count( $data['volumes'] ) > 1 ){
					// Tratamento dos dados
					// Cada volume/pacote deve ser um ticket
					$volumes 		= $data['volumes'];
					$unitaryWeight 	= $data['products'][0]['weight'];
					$unityVal 		= $data['products'][0]['unitary_value'];
					$newData 		= array();

					foreach( $volumes as $package ){
						$qty 				= $package['weight'] / $unitaryWeight;
						$insuranceVal 		= $qty * $unityVal;
						$data['volumes'] 	= array($package);

						$newData[] = $data;
					}

					// Requisição das etiquetas
					foreach( $newData as $data ){
						$ticketsInCart[$storeFreight][] = $this->requestService->request( $this->routeCart, $this->typeRequestPost, $data );
					}
				} else {
					$ticketsInCart[$storeFreight] = $this->requestService->request( $this->routeCart, $this->typeRequestPost, $data );
				}
			}
			
			if( !empty( $ticketsInCart ) ){
				$this->ticketService->saveTicketDataOnOrder( $order_id, $ticketsInCart );
			} else {
				# Enviar mensagem de erro
			}
		}
	}

	/**
	 * Salvar dados retornados pela cotação no pedido
	 * 
	 * @param integer $order_id
	 * @param object/mixed $posted_data
	 * @param object $order
	 */
	public function action_checkout_order_processed( $order_id, $posted_data, $order ) {
		
		$vendors 	= $this->helpers->sanitizeStoreVendorsInOrder( $order_id );
		if( empty( $vendors ) ) return;

		foreach( $vendors as $vendorId => $data ){
			$meta_key 	= '_milogFreight_' . $vendorId;
			$meta_value = $_COOKIE[$meta_key];
			
			update_post_meta( $order_id, $meta_key, $meta_value );
		}
	}

	public function milogAuthMelhorEnvio( $atts )
	{
		$a = shortcode_atts( 
			[
				'color'	=> 'default',
				'size'	=> 'default',
				'type'	=> 'type_1'
			], 
			$atts
		);

		$code 		= $_GET['code'];
		$codeExists = false;
		$content 	= '';

		# Checking code exists
		if( $code && !empty($code) ){
			$codeExists = true;
		}

		if( $codeExists ){
			$tokenService       = new Milog_Token_Service();
			$key 				= '_me_auth_code';
			update_option( $key, $code );
			$savedCode 			= get_option( $key );
 			$content 	= '<h2 class="">Autorização do applicativo configurada com sucesso!</h2>';
			$content 	.= '<div id="containerGetToken" class="">
				<p class="">Clique no botão abaixo para solicitar um token para uso.</p>
				<button class="uk-button uk-button-default" onclick="getToken()">Solicitar Token</button>
			</div>';

		} else {

			$url 			= 'https://melhorenvio.com.br';
			$clientId 		= 5928;
			$callbackURI 	= 'https://mercadoindustria.com.br/autorizacao-melhor-envio';
			$scope 			= 'cart-read cart-write companies-read companies-write coupons-read coupons-write notifications-read orders-read products-read products-write purchases-read shipping-calculate shipping-cancel shipping-checkout shipping-companies shipping-generate shipping-preview shipping-print shipping-share shipping-tracking ecommerce-shipping transactions-read users-read users-write';
			$location 		= $url . '/oauth/authorize?client_id='. $clientId .'&redirect_uri='. $callbackURI .'&response_type=code&scope='. $scope;
			$authButton 	= '<a href="'. $location .'" class="">Autorizar Aplicativo</a> ';

			$content 		= $authButton;

		}
		
		return $content;
	}
}
