<?php
/**
 * Provide ticket logic to send from cart and buy
 * *
 * @link       unitycode.tech
 * @since      1.0.0
 *
 * @package    Milog
 * @subpackage Milog/includes
 */
class Milog_Ticket
{
    protected $helpers;

    public function __construct()
    {
        $this->helpers = new Milog_Helpers();
    }
    
	/**
	 * Responsável por organizar os dados que serão enviados na solicitação de envio para o carrinho melhor envio
     * Retorna $bodyStructure completo e pronto para enviar ao carrinho
	 * 
	 * @param int $orderId
	 * @access public
	 * @return object $bodyStructure
	 * https://stackoverflow.com/questions/46102428/get-orders-shipping-items-details-in-woocommerce-3
     * 
	 */
	public function sanitize_freights_to_cart( $orderId )
	{
		$order              = wc_get_order( $orderId );
        $orderUrl           = $order->get_view_order_url();
		$bodyStructure      = $this->helpers->sanitizeSelectedFreightService( $order ); # Get selected freight
        $bodyStructure      = $this->helpers->getStoreAddressFromOrder( $bodyStructure, $order ); # Get Address from
        $bodyStructure      = $this->helpers->getCustomerAddressFromOrder( $bodyStructure, $order ); # Get Address To
        $bodyStructure      = $this->helpers->getProductsFromOrder( $bodyStructure, $order ); # Get products from order
        $bodyStructure      = $this->helpers->sanitizeProductsVolumes( $bodyStructure, $order); # Get volumes from products order
        $bodyStructure      = $this->helpers->setupOptions( $bodyStructure, $order ); # Get options setup

        return $bodyStructure;
	}

    /**
     * Método responsável por salvar dados do frete cotado no carrinho melhor envio
     * 
     * @param integer $orderId
     * @param object $cartItems
     * 
     * @return void
     */
    public function saveTicketDataOnOrder( $orderId, $tickets )
    {
        $field = 'slug';
        foreach( $tickets as $store => $data ){
            $storeData  = get_user_by( $field, $store );
            $storeId    = $storeData->ID;
            
            $keysMap = [
                'id'        => '_' . $storeId . '_ticket_id',
                'protocol'  => '_' . $storeId . '_ticket_protocol',
                'status'    => '_' . $storeId . '_ticket_status',
                'created_at'=> '_' . $storeId . '_ticket_created_at',
                'updated_at'=> '_' . $storeId . '_ticket_updated_at'
            ];

            foreach( $keysMap as $key => $metaKey ){
                update_post_meta( $orderId, $metaKey, $data->$key );
            }
        }
    }
    
    /**
	 * Método de compra dos itens no carrinho melhor envio
	 * 
	 * @param string $ticketId
	 * @param string $typeRequest
	 * 
	 * @return object $response
	 */
	public function purchaseCartItems( $ticketId )
	{
		$route 			= '/shipment/checkout';
		$typeRequest 	= 'POST';
		$body['orders'] = [
			$ticketId,
		];

		$request = $this->requestService->request( $route, $typeRequest, $body );

		return $request;
	}

	/**
	 * Método que gera a etiqueta comprada antes de disponibilizar para impressão
	 * 
	 * @param string $purchaseId
	 * 
	 * @return void
	 */
	public function generateTicket( $purchaseId )
	{
		$route 			= '/shipment/generate';
		$typeRequest 	= 'POST';
		$body			= array();
		$body['orders'] = [
			$purchaseId,
		];

		$request = $this->requestService->request( $route, $typeRequest, $body );

		return $request;
	}
}