<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       unitycode.tech
 * @since      1.0.0
 *
 * @package    Milog
 * @subpackage Milog/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Milog
 * @subpackage Milog/public
 * @author     jnz93 <box@unitycode.tech>
 */
class Milog_Public {

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
	 * Requests
	 */
	private $requestService;
	private $ticketService;
	private $helpers;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct() {

		$this->plugin_name 		= $plugin_name;
		$this->version 			= $version;

		$this->requestService	= new Milog_Request_Service();
		$this->ticketService 	= new Milog_Ticket();
		$this->helpers          = new Milog_Helpers();

		/**
		 * Enqueue scripts
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/**
		 * Filters para adicionar nova coluna na tabela de pedidos da loja
		 */
		add_filter( 'wcfm_orders_additional_info_column_label', array( $this, 'additional_colunm_store_orders' ) );
		add_filter( 'wcfm_orders_additonal_data_hidden', '__return_false' );
		add_filter( 'wcfm_orders_additonal_data', array( $this, 'additional_column_data_store_orders' ), 50, 2 );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/milog-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/milog-public.js', array( 'jquery' ), $this->version, false );

		/**
		 * Configurando o arquivo e váriaves JS para requisições AJAX
		 */
		wp_enqueue_script( 'milog_store_ajax', plugin_dir_url( __FILE__ ) . 'js/milog-stores-ajax.js', '', '1.0', true );
		wp_localize_script( 'milog_store_ajax', 'storeAjax', array(
			'url'	=> admin_url( 'admin-ajax.php' ),
			'nonce'	=> wp_create_nonce( 'store-ajax-nonce' )
		));
	}


	/**
	 * Adicionando a coluna Etiqueta(s) na página de pedidos do painel WCFM
	 */	
	public function additional_colunm_store_orders( $affiliate_column_label )
	{
		$affiliate_column_label = 'Etiqueta(s)';
		return $affiliate_column_label;
	}
	
	/**
	 * Adicionando os botões necessários na coluna Etiqueta(s) na página de pedidos do painel WCFM
	 */
	public function additional_column_data_store_orders( $affiliate_column_data, $order_id ) {

		$order 				= wc_get_order( $order_id );
		$order_status 	 	= $order->get_status();
		$storeId 			= get_current_user_id();
		$ticketPurchased 	= get_post_meta( $order_id, '_' . $storeId . '_ticket_status', true );
		$buttons 			= '';
		if( $order_status == 'completed' ) {
			$buttons 	.= '<button class="" data-action="purchase-ticket" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(this)" style="margin-bottom: 5px;">Comprar</button>';
			$buttons 	.= '<button class="" data-action="print-ticket" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(this)" style="margin-bottom: 5px;">Imprimir</button>';
			$buttons 	.= '<button class="" data-action="cancel-ticket" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(this)" style="margin-bottom: 5px;">Cancelar</button>';
			// $cartButtons 		.= '<button class="" data-action="remove-cart" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(this)" style="margin-bottom: 5px;">Remover</button>';
			// $purchasedButtons 	.= '<button class="" data-action="tracking-ticket" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(this)" style="margin-bottom: 5px;">Rastrear</button>';
		} else {
			$buttons = '<span class="">Recurso indisponível</span>';
		}


		$affiliate_column_data = $buttons;
		return $affiliate_column_data;
	}
}