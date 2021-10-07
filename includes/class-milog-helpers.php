<?php
/**
 * Fornece métodos públicos para funções gerais que auxiliam outras classes
 *
 * @link       unitycode.tech
 * @since      1.0.0
 *
 * @package    Milog
 * @subpackage Milog/includes
 */
class Milog_Helpers{

    protected $requestService;

    public function __construct()
    {
        $this->requestService = new Milog_Request_Service();
    }
    /**
     * Recebe a estrutura da requisição e o pedido;
     * Retorna $body com o endereço de coleta referente a loja
     * 
     * @param array $body
     * @param object $order
     * 
     * @return array $body
     */
    public function getStoreAddressFromOrder( $body, $order ) 
    {
        $products = $order->get_items();
        $dataFrom = array();
        # Coletando o endereço das lojas no pedido
        foreach( $products as $item_id => $item ){
            $product_id     = $item->get_product_id();
            $storeId        = wcfm_get_vendor_id_by_post( $product_id );
            $store          = get_user_meta( $storeId, 'wcfmmp_profile_settings', true );
            $storeLocation  = $store['address'];
            $storeNumber    = explode( ',', $storeLocation['street_1'] );
            $storeNumber    = $storeNumber[1];

            if( array_key_exists( $storeId, $dataFrom ) ) continue;
            $dataFrom[$storeId] = [
                'name'              => $store['store_name'],
                'phone'             => str_replace( ['(', ')', '-', ' '], ['', '', '', ''], $store['phone'] ),
                'email'             => $store['store_email'],
                'document'          => "", # CPF do vendedor(opcional)
                'company_document'  => "89794131000100", # CNPJ do vendedor
                'state_register'    => "123456", # Inscrição estadual
                'address'           => $storeLocation['street_1'],
                'complement'        => "", # Complemento
                'number'            => $storeNumber,
                'district'          => "", # Bairro
                'city'              => $storeLocation['city'],
                'country_id'        => $storeLocation['country'],
                'postal_code'       => str_replace( '-', '', $storeLocation['zip'] ),
                'note'              => ""
            ];
        }

        # Atribuindo "From" para o $body
        $bodyStructure = $body;
        foreach( $dataFrom as $store => $from ){
            $bodyStructure[$store]['from'] = $from;
        }

        return $bodyStructure;
    }

    /**
     * Recebe a estrutura da requisição e o pedido;
     * Retorna $body com o endereço de entrega referente ao cliente
     * 
     * @param array $body
     * @param object $order
     * 
     * @return array $body
     */
    public function getCustomerAddressFromOrder( $body, $order )
    {
        $customerId                 = $order->get_customer_id();
        $customerFullName           = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
        $customerPhone              = str_replace( ['(', ')', '-', ' '], ['', '', '', ''], $order->get_billing_phone() );
        $customerEmail              = $order->get_billing_email();
        $customerCompany            = $order->get_shipping_company();
        $customerAddress            = !empty($order->get_shipping_address_1()) ? $order->get_shipping_address_1() : $order->get_shipping_address_2();
        $customerCity               = $order->get_shipping_city();
        $customerState              = $order->get_shipping_state();
        $customerPostcode           = str_replace( '-', '', $order->get_shipping_postcode() );
        $customerCountry            = $order->get_shipping_country();
        $customerCpf                = $order->get_meta('_billing_cpf');
        $customerCnpj               = $order->get_meta('_billing_cnpj');
        $customerNeighborhood       = $order->get_meta('_billing_neighborhood');
        $customerNumber             = $order->get_meta('_billing_number');

        $dataTo    = array(
            'name'              => $customerFullName,
            'phone'             => $customerPhone,
            'email'             => $customerEmail,
            'document'          => $customerCpf,
            'company_document'  => strlen($customerCnpj) != 0 ? $customerCnpj : '',
            'state_register'    => "123456",
            'address'           => $customerAddress,
            'complement'        => "Complemento",
            'number'            => $customerNumber,
            'district'          => "Bairro",
            'city'              => $customerCity,
            'state_abbr'        => $customerState,
            'country_id'        => $customerCountry,
            'postal_code'       => $customerPostcode,
            'note'              => ""
        );

        # Atribuindo "to" para o $body
        $bodyStructure = $body;
        foreach( $body as $store => $value ){
            $bodyStructure[$store]['to'] = $dataTo;
        }

        return $bodyStructure;
    }

    /**
     * Coleta os produtos dentro do pedido
     * Retorna $body com os produtos do pedido
     * 
     * @param object $order
     * @param array $body
     * 
     * @return array $body
     */
    public function getProductsFromOrder( $body, $order )
    {
        $products           = $order->get_items();
        $sanitizedProducts  = array();

        if( empty( $products ) ) return;

        foreach( $products as $key => $item ){

            $product_id     = $item->get_product_id();
            $storeId        = wcfm_get_vendor_id_by_post( $product_id );
            
            $product            = $item->get_product();
            $productName        = $product->get_name();
            $productPrice       = $product->get_price();
            $productQuantity    = $item->get_quantity();
            $productWeight      = get_post_meta( $product_id, '_weight', true );

            # Atribuindo os produtos para respectiva loja em $body
            $body[$storeId]['products'][] = [
                'name'          => $productName,
                'quantity'      => $productQuantity,
                'unitary_value' => $productPrice,
                'weight'        => $productWeight,
            ];
        }
        return $body;
    }

    /**
     * Retorna os volumes do pacote de frete por loja
     * 
     * @param object $body
     * @param object $order
     * @return array $body
     */
    public function sanitizeFreightVolumes( $body, $order )
    {
        $orderId            = $order->get_id();
        $volumes            = array();
        $metaKeys           = array();
        $shippingServices   = $this->sanitizeShippingServicesInOrder( $orderId );
        foreach( $body as $key => $data ){

            $metaKey            = '_milogFreight_' . $key;
            $storeFreightData   = json_decode(get_post_meta( $orderId, $metaKey, true ));
            $service            = $shippingServices[$key];
            $freightData        = $storeFreightData->$service;

            $body[$key]['volumes'][] = [
                'height'    => $freightData->height,
                'width'     => $freightData->width,
                'length'    => $freightData->length,
                'weight'    => $freightData->weight,
            ];
        }
        return $body;
    }

    /**
     * Retorna o array de "Options" da requisição dentro de $body
     * 
     * @param array $body
     * @param object $order
     * 
     * @return array $body
     */
    public function setupOptions( $body, $order )
    {
        if( empty( $body ) ) return $body;

        $orderId    = $order->get_id();
        $orderUrl   = $order->get_view_order_url();

        # Iterando por lojas
        foreach( $body as $store => $data ){
            $insuranceValue = 0;
            $products       = $data['products'];

            # Calculando o valor do seguro
            if( empty( $products ) ) continue;
            foreach( $products as $product ){
                $insuranceValue += $product['unitary_value'] * $product['quantity'];
            }

            # Inserindo o "options" no body por loja
            $body[$store]['options'] = [
                'insurance_value'   => $insuranceValue,
                'receipt'           => false,
                'own_hand'          => false,
                'reverse'           => false,
                'non_commercial'    => true,
                'invoice'           => [
                    'key'           => '',
                ],
                'plataform'         => 'Mercado Indústria',
                'tags'              => [
                    'tag'           => $orderId,
                    'url'           => $orderUrl
                ],
            ];

            unset($insuranceValue);
        }
        return $body;
    }

    /**
     * Listar empresas disponíveis pelo melhor envio
     * 
     * @return array $companies
     */
	public function getAvailableCompanies()
    {
        /**
         * Mais tarde podemos modificar a função para salvar em um option os dados retornados desse endpoint
         */
        $route = '/shipment/companies';

        $companies = $this->requestService->requestCompanies($route);

        return $companies;
    }

    /**
     * Listar serviços disponíveis pelo melhor envio
     * 
     * @return array $services
     */
    public function getAvailableServices()
    {
        $route  = '/shipment/services';
        $data   = $this->requestService->requestCompanies($route);

        $servicesList = $this->sanitizeServices( $data );
        return $servicesList;
    }
    
    /**
     * Recebe $list e organiza os dados para retornar $services
     * 
     * @param array $list
     * @return object $services 
     */
    public function sanitizeServices( $list )
    {
        if( empty( $list ) || !is_array( $list ) ) return;
        
        $services = array();
        foreach( $list as $item ) {
            $services[$item->name] = array(
                'id'        => $item->id,
                'type'      => $item->type,
                'company'   => $item->company->name
            );
        }

        return $services;
    }

    /**
     * Recebe $order e cria a primeira versão de $bodyStructure com o serviços de frete do pedido organizados por loja
     * 
     * @param object $order
     * @return array $bodyStructure
     */
    public function sanitizeSelectedFreightService( $order )
    {
        if( empty( $order ) ) return;

        # Definição dos serviços de entrega selecionados pelo cliente na compra
        $shippingData   = $order->get_items('shipping');
        $bodyStructure  = array();
        foreach( $shippingData as $key => $data )
        {
            $itemData       = $data->get_data();
            $deliveryTitle  = $itemData['method_title'];
            $storeId        = $data->get_meta('vendor_id');
            
            $bodyStructure[$storeId] = array(
                'method_title'  => $deliveryTitle,
            );
        }

        $bodyStructure = $this->sanitizeServiceId( $bodyStructure );
        return $bodyStructure;
    }

    /**
     * Recebe $bodyStructure com o nome do serviço selecionado
     * Retorna $bodyStructure substituindo o título do serviço pelo ID
     * @param array $bodyStructure
     * @return array $bodyStructure
     */
    public function sanitizeServiceId( $bodyStructure )
    {
        # Serviços disponíveis pela plataforma melhor envio
        $availableServices  = $this->getAvailableServices();
        $body               = $bodyStructure;
        $structure          = array();
        foreach( $bodyStructure as $key => $data ){
            $methodTitle    = explode( ' ' , $data['method_title'] );
            $methodTitle    = trim( $methodTitle[1] );
            $structure = array(
                'service'   => $availableServices[$methodTitle]['id'],
                'type'      => $availableServices[$methodTitle]['type'],
                'company'   => $availableServices[$methodTitle]['company'],
            );
            $bodyStructure[$key] = $structure;
        }
        return $bodyStructure;
    }

    /**
	 * Sanitize retorno da compra de etiquetas
	 * 
	 * @param object $response
	 * @return string $sanitizedData
	 */
	public function sanitizePurchasedResponse( $response )
	{
		$sanitizedData = array(
			'purchased_id'			=> $response->purchase->id,
			'purchased_protocol'	=> $response->purchase->protocol,
			'purchased_status'		=> $response->purchase->status,
			'purchased_paid_at'		=> $response->purchase->paid_at,
			'purchased_orders_id'	=> $response->purchase->orders[0]->id,
		);

		return $sanitizedData;
	}

    /**
     * Sanitize retorno da requisição de rastreio
     * 
     * @param object $response
     * @return mixed $sanitizedData
     */
    public function sanitizeTrackingResponse( $response )
    {
        $sanitizedData;
        foreach( $response as $key => $data ){
            $sanitizedData = [
                'status'        => $data->status,
                'tracking_code' => $data->tracking,
                'posted_at'     => $data->posted_at
            ];
        }

        return $sanitizedData;
    }

    /**
     * Retornar todos os vendedores em um pedido
     * 
     * @param integer $order_id
     * @return array $storeVendors
     */
    public function sanitizeStoreVendorsInOrder( $order_id )
    {
        $storeVendors = array();
        if( function_exists( 'wcfm_get_vendor_store_by_post' ) ) {
            $order = wc_get_order( $order_id );
            if( is_a( $order , 'WC_Order' ) ) {
                $items = $order->get_items( 'line_item' );
                if( !empty( $items ) ) {
                    foreach( $items as $orderItemID => $item ) {
                        $line_item  = new WC_Order_Item_Product( $item );
                        $product    = $line_item->get_product();
                        $productId  = $line_item->get_product_id();
                        $vendorId   = wcfm_get_vendor_id_by_post( $productId );
                        
                        if( !$vendorId ) continue;
                        if( in_array( $vendorId, $storeVendors ) ) continue;
                        
                        $storeName = wcfm_get_vendor_store( $vendorId );
                        if( $storeName ) {
                            $storeVendors[$vendorId] = $storeName;
                        }
                    }
                }
            }
        }

        return $storeVendors;
    }

    /**
     * Retorna uma lista de serviços de entrega contratados no pedido
     * organizada por vendedores
     * 
     * @param integer $order_id
     * @return string $shippingServices
     */
    public function sanitizeShippingServicesInOrder( $order_id )
    {
        $order              = wc_get_order( $order_id );
        $shippingServices   = array();
        $shippingData       = $order->get_shipping_methods();

        foreach( $shippingData as $id => $data ){
            # Get store id
            $storeId        = $data->get_meta( 'vendor_id', true);

            # Get method title
            $itemData           = $data->get_data();
            $shippingName       = $itemData['name'];
            $shippingTitle      = $itemData['method_title'];
            if( strlen( $shippingName ) == 0 ){
                $shippingName = $shippingTitle;
            }

            # Sanitize service name
            $shippingName = explode( ' ', $shippingName );
            if( !empty( $shippingName ) ){
                $shippingServices[$storeId] = trim( $shippingName[1] );
            }
        }
        return $shippingServices;
    }
}