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
	private $tokenService;

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
		$this->tokenService     = new Milog_Token_Service();

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

		/**
		 * Filters para adicioanr nova coluna na tabela de pedidos do cliente
		 */
		add_filter( 'woocommerce_my_account_my_orders_columns', array( $this, 'additional_columns_customer_orders_list' ) );
		add_filter( 'woocommerce_my_account_my_orders_column_order-shipment-track', array( $this, 'add_buttons_for_shipment_actions_in_customer_orders_list' ) );

		/**
		 * Ajax action
		 */
		add_action( 'wp_ajax_milog_store_service_request', array( $this, 'milog_store_service_request_callback') );
		add_action( 'wp_ajax_nopriv_milog_store_service_request', array( $this, 'milog_store_service_request_callback') );

		# Token request
		add_action( 'wp_ajax_milog_token_request', array( $this, 'milog_token_request_callback') );

		/**
		 * Add spinner actions
		 */
		add_action( 'wp_head', array( $this, 'add_loading_spinner_css') );
		add_action( 'wp_footer', array( $this, 'add_loading_spinner_template') );

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
	 * 
	 * @param mixed $affiliate_column_data
	 * @param integer $order_id
	 * 
	 * @return mixed $affiliate_column_data
	 */
	public function additional_column_data_store_orders( $affiliate_column_data, $order_id ) {

		$order 				= wc_get_order( $order_id );
		$order_status 	 	= $order->get_status();
		$storeId 			= get_current_user_id();
		$ticket_status		= get_post_meta( $order_id, '_' . $storeId . '_ticket_purchased_status', true );
		$output 			= '';
		if( $order_status == 'completed' ) {

			if( $ticket_status == 'paid' ){
				$output 	.= '<button style="margin-bottom: 5px; display: block;" class="after-paid" data-action="print-ticket" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(jQuery(this))">Imprimir</button>';
				$output 	.= '<button style="margin-bottom: 5px; display: block;" class="after-paid" data-action="tracking-ticket" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(jQuery(this))">Rastrear</button>';
				$output 	.= '<button style="margin-bottom: 5px; display: block;" class="after-paid" data-action="cancel-ticket" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(jQuery(this))">Cancelar</button>';	
			} else {
				$output 	.= '<button style="margin-bottom: 5px; display:block;" class="before-paid" data-action="purchase-ticket" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(jQuery(this))">Comprar</button>';
				// $output 	.= '<button style="margin-bottom: 5px; display:block;" class="before-paid" data-action="remove-cart" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(jQuery(this))">Remover</button>';
				
				$output 	.= '<button style="margin-bottom: 5px; display: none;" class="after-paid" data-action="print-ticket" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(jQuery(this))">Imprimir</button>';
				$output 	.= '<button style="margin-bottom: 5px; display: none;" class="after-paid" data-action="tracking-ticket" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(jQuery(this))">Rastrear</button>';
				$output 	.= '<button style="margin-bottom: 5px; display: none;" class="after-paid" data-action="cancel-ticket" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(jQuery(this))">Cancelar</button>';	
			}

		} else {
			$output = '<span class="">Etiqueta Indisponível</span>';
		}

		$affiliate_column_data = $output;
		return $affiliate_column_data;
	}

	/**
	 * Adicionando novas colunas na tabela de pedidos do painel do cliente WC
	 * 
	 * @param array $columns
	 */
	public function additional_columns_customer_orders_list( $columns )
	{
		$newColumns = array();

		foreach( $columns as $key => $name ){
			$newColumns[$key] = $name;

			if( $key === 'order-actions' ){
				$newColumns['order-shipment-track'] = __( 'Entrega', 'milog' );
			}
		}

		return $newColumns;
	}

	/**
	 * Adicionando o botão "Rastrear pacote" na coluna "entrega"
	 * Tabela de pedidos do cliente
	 * 
	 * @param object $order
	 */
	public function add_buttons_for_shipment_actions_in_customer_orders_list( $order )
	{
		$order_id 		= $order->get_id();
		$items 			= $order->get_items();

		# Coletando lojas no pedido
		$stores 		= array();
		if( !empty( $items ) ){
			foreach( $items as $product => $data ){
				$productId      = $data->get_product_id();
				$storeId    	= wcfm_get_vendor_id_by_post( $productId );

				if( !in_array( $storeId, $stores ) ){
					$stores[]	= $storeId;
				}
			}
		}
		
		# Criando os botões com base no número de lojas
		$buttons 		= '';
		if( !empty( $stores ) ){
			foreach( $stores as $storeId ){
				$buttons .= '<button class="" data-action="tracking-ticket" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(jQuery(this))" style="margin-bottom: 5px;">Rastrear Pacote</button>';
				$buttons .= '<button class="" data-action="cancel-ticket" data-order-id="'. $order_id .'" data-store-id="'. $storeId .'" onclick="milogTicketRequest(jQuery(this))" style="margin-bottom: 5px;">Cancelar Envio</button>';
			}
		}

		echo $buttons;
	}

	/**
	 * Callback para requisições ajax do painel da loja
	 */
	public function milog_store_service_request_callback()
	{
		check_ajax_referer( 'store-ajax-nonce', 'nonce' );
		$nonce 		= $_POST['nonce'];
		$type 		= $_POST['type'];
		$orderId 	= $_POST['orderId'];
		$storeId 	= $_POST['storeId'];
		$response	= '';

		$keysMap = [
			'_'. $storeId .'_ticket_id',
			'_'. $storeId .'_ticket_protocol',
			'_'. $storeId .'_ticket_status',
			'_'. $storeId .'_ticket_created_at',
			'_'. $storeId .'_ticket_updated_at'
		];

		$ticketId 			= get_post_meta( $orderId, '_'. $storeId .'_ticket_id', true );
		$purchasedTicketId 	= get_post_meta( $orderId, '_' . $storeId . '_ticket_purchased_orders_id', true );
		
		// echo 'Id: ' . $purchasedTicketId;
		switch ( $type ) {
			case 'purchase-ticket':
				$purchasedResponse  = $this->ticketService->purchaseCartItems( $ticketId );
				$sanitizedResponse 	= $this->helpers->sanitizePurchasedResponse( $purchasedResponse );
				$response 			= $this->ticketService->savePurchasedTicketDataOnOrder( $sanitizedResponse, $orderId, $storeId ); # Salvando dados da compra no pedido
				$response 			= 'success';
				break;

			case 'print-ticket':
				/**
				 * Outra ideia é chamar os dois métodos abaixo na compra da etiqueta
				 * Dessa forma podemos salvar a URL de impressão em um meta-campo do pedido
				 * Com isso sempre que quiser imprimir a etiqueta não repetira as requisições
				 */
				$request = $this->ticketService->generateTicket( $purchasedTicketId );
				$request = $this->ticketService->printTicket( $purchasedTicketId );
				$response = $request->url;
				break;

			case 'tracking-ticket':
				/**
				 * Criar método para salvar os dados de retorno dessa requisição. Talvez o ideal seja realocar esses métodos
				 * para actions wp-cron salvando os dados de retorno no banco wp. Dessa forma o usuário não precisa fazer uma requisição
				 * para o melhor envio todas as vezes que clicar em rastreio.
				 */
				$response 			= $this->ticketService->trackTicket( $purchasedTicketId );
				$sanitizedResponse 	= $this->helpers->sanitizeTrackingResponse( $response );
				$response 			= $sanitizedResponse['status'];
				break;

			case 'cancel-ticket':
				$response = $this->ticketService->isCancellableTicket( $purchasedTicketId );
				break;
	
			default:
				echo 'Tipo ' . $type . ' Inválido!';
				break;
		}
		
		die($response);
	}

	/**
	 * Css spinner loading
	 */
	public function add_loading_spinner_css()
	{
		$output = '<style>
			#container-spinner{
				background: rgba(0, 0, 0, 0.8);
				position: fixed;
				top: 0;
				right: 0;
				bottom: 0;
				left: 0;
				z-index: 999;
				display: flex;
				align-items: center;
				justify-content: center;
			}
			.disabled-spinner{
				display: none !important;
			}
			.lds-grid {
				display: inline-block;
				position: relative;
				width: 80px;
				height: 80px;
			}
			.lds-grid div {
				position: absolute;
				width: 16px;
				height: 16px;
				border-radius: 50%;
				background: #fff;
				animation: lds-grid 1.2s linear infinite;
			}
			.lds-grid div:nth-child(1) {
				top: 8px;
				left: 8px;
				animation-delay: 0s;
			}
			.lds-grid div:nth-child(2) {
				top: 8px;
				left: 32px;
				animation-delay: -0.4s;
			}
			.lds-grid div:nth-child(3) {
				top: 8px;
				left: 56px;
				animation-delay: -0.8s;
			}
			.lds-grid div:nth-child(4) {
				top: 32px;
				left: 8px;
				animation-delay: -0.4s;
			}
			.lds-grid div:nth-child(5) {
				top: 32px;
				left: 32px;
				animation-delay: -0.8s;
			}
			.lds-grid div:nth-child(6) {
				top: 32px;
				left: 56px;
				animation-delay: -1.2s;
			}
			.lds-grid div:nth-child(7) {
				top: 56px;
				left: 8px;
				animation-delay: -0.8s;
			}
			.lds-grid div:nth-child(8) {
				top: 56px;
				left: 32px;
				animation-delay: -1.2s;
			}
			.lds-grid div:nth-child(9) {
				top: 56px;
				left: 56px;
				animation-delay: -1.6s;
			}
			@keyframes lds-grid {
				0%, 100% {
					opacity: 1;
				}
				50% {
					opacity: 0.5;
				}
			}
		</style>';

		echo $output;
	}

	/**
	 * Template spinner loading
	 */
	public function add_loading_spinner_template()
	{
		$output = '<div id="container-spinner" class="disabled-spinner"><div class="lds-grid"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>';
		echo $output;
	}

	/**
	 * Action que recebe a solicitação do token e retorna um novo
	 * 
	 */
	public function milog_token_request_callback()
	{
		$request = $this->tokenService->getDataToken();
		if( empty($request) ) return;

		$data 		= $request;
		$is_saved 	= $this->tokenService->saveToken( $data );

		echo $is_saved;
		die();
	}
}