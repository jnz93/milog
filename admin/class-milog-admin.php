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
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name 		= $plugin_name;
		$this->version 			= $version;
		$this->typeRequestPost  = 'POST';
		$this->typeRequestGet	= 'GET';
		$this->routeCart        = '/cart';

		/**
		 * Actions & Filters
		 */
		add_action( 'woocommerce_order_status_changed', array( $this, 'when_order_is_completed' ), 99, 4 );
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
				$ticketsInCart[$storeFreight] = $this->requestService->request( $this->routeCart, $this->typeRequestPost, $data );
			}
			
			if( !empty( $ticketsInCart ) ){
				$this->ticketService->saveTicketDataOnOrder( $order_id, $ticketsInCart );
			} else {
				# Enviar mensagem de erro
			}
		}
	}
}
