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
}