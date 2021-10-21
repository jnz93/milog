<?php
/**
 * Provide the Request quotation
 * *
 * @link       unitycode.tech
 * @since      1.0.0
 *
 * @package    Milog
 * @subpackage Milog/includes
 */
class Milog_Request_Service
{
	# Config Costants 
	const URL  			= 'https://melhorenvio.com.br/api/v2/me';
	const SANDBOX_URL 	= 'https://sandbox.melhorenvio.com.br/api/v2/me';
	const TIMEOUT 		= 10;

	# Constant com limite de tempo para uma requisição HTTP, se passar disso, um log dessa requisição será gerado
	const TIME_LIMIT_LOG_REQUEST = 1000;

	protected $token;
	protected $tokenType;
	protected $url;
	protected $headers;
	protected $headersCompanies;
	protected $headersCart;
	protected $headersAuth;
	protected $tokenService;

	public function __construct()
	{
		$this->tokenService = new Milog_Token_Service();
		$this->tokenType 	= $this->tokenService->getTypeToken();
		$this->token 		= $this->tokenService->getToken();
		$this->url 			= self::URL;

		$this->headers 		= array(
			'Accept'		=> 'application/json',
			'Content-Type'	=> 'application/json',
			'Authorization'	=> 'Bearer ' . $this->token,
			'User-Agent'	=> 'Mercado Indústria logs@unitycode.tech'
		);
		$this->headersCompanies = array(
			'User-Agent' 	=> 'Mercado Indústria logs@unitycode.tech'
		);
		$this->headersCart 	= array(
			'Accept' 		=> 'application/json',
			'Authorization'	=> 'Bearer ' . $this->token,
			'User-Agent' 	=> 'Aplicação Mercado Indústria logs@unitycode.tech'
		);
		$this->headersAuth = array(
			'Accept'		=> 'application/json',
			'User-Agent'	=> 'Mercado Indústria (logs@unitycode.tech)'
		);
	}

    /**
	 * Metódo responsável por fazer a requisição para API Melhor Envio
	 * 
	 * @param string $route
	 * @param string $typeRequest
	 * @param array $body
	 * @return object $response
	 */
	public function request( $route, $typeRequest, $body, $useJson = true )
	{
		if( $useJson ) {
			$body = json_encode( $body );
		}

		$params 	= array(
			'headers'	=> $this->headers,
			'method'	=> $typeRequest,
			'body'		=> $body,
			'timeout'	=> self::TIMEOUT
		);

		$time_pre 	= microtime( true );

		$responseRemote = wp_remote_post( $this->url . $route, $params );
		$response 		= json_decode(
			wp_remote_retrieve_body( $responseRemote )
		);

		$time_post 	= microtime( true );
		$exec_time 	= round( ( $time_post - $time_pre ) * 1000 );
		
		$responseCode = ( !empty( $responseRemote['response']['code'] ) ) ? $responseRemote['response']['code'] : null ;

		if( $responseCode != 200 ) {
			// clearDataStored->clear();
		}
		
		if( empty( $response ) ) {
			return (object) [
				'success'	=> false,
				'errors'	=> ['Ocorreu um erro ao se conectar com a API do Melhor Envio'],
			];
		}

		if( !empty( $response->message ) && $response->message == 'Unauthenticated.') {
			# ( new SessionNoticeService())->add('Verificar seu token Melhor Envio');
			return (object) [
				'success'	=> false,
				'errors'	=> ['Usuário não autenticado'],
			];
		}

		# $errors = $this->treatmentErrors( $response );
		$errors = '';
		if( !empty( $errors ) ) {
			return (object) [
				'success'	=> false,
				'errors'	=> $errors,
			];
		}

		return $response;
	}

	/**
     * Function to make auth code request to api melhor envio
     * 
     * @param string $route
     * @param string $typeRequest
     * @param array $body
     * 
     * @return object $response
     */
    public function requestAuth( $route, $typeRequest, $body, $useJson = true )
    {
		if( $useJson ) {
			$body = json_encode( $body );
		}

		$fucking_url = 'https://melhorenvio.com.br';
		$headers = array(
			'Accept'		=> 'application/json',
			'User-Agent'	=> 'Aplicação (email para contato técnico)'
		);
		
		$params 	= array(
			"headers"	=> $headers,
			"method"	=> $typeRequest,
			"body"		=> $body,
			"timeout"	=> 0
		);

		$time_pre 	= microtime( true );

		$responseRemote = wp_remote_post( $fucking_url . $route, $params );
		$response 		= json_decode(
			wp_remote_retrieve_body( $responseRemote )
		);

		$time_post 	= microtime( true );
		$exec_time 	= round( ( $time_post - $time_pre ) * 1000 );
		
		$responseCode = ( !empty( $responseRemote['response']['code'] ) ) ? $responseRemote['response']['code'] : null ;

		if( $responseCode != 200 ) {
			// clearDataStored->clear();
		}
		
		if( empty( $response ) ) {
			return (object) [
				'success'	=> false,
				'errors'	=> ['Ocorreu um erro ao se conectar com a API do Melhor Envio'],
			];
		}

		if( !empty( $response->message ) && $response->message == 'Unauthenticated.') {
			# ( new SessionNoticeService())->add('Verificar seu token Melhor Envio');
			return (object) [
				'success'	=> false,
				'errors'	=> ['Usuário não autenticado'],
			];
		}

		# $errors = $this->treatmentErrors( $response );
		$errors = '';
		if( !empty( $errors ) ) {
			return (object) [
				'success'	=> false,
				'errors'	=> $errors,
			];
		}
		return $response;
	}

	/**
	 * Function to make companies request from api melhor envio
	 * 
	 * @param string $route
	 * @param string $typeRequest
	 */
	public function requestCompanies( $route, $typeRequest = 'GET' )
	{
		$params 	= array(
			'headers'	=> $this->headersCompanies,
			'method'	=> $typeRequest,
			'timeout'	=> self::TIMEOUT
		);

		$time_pre 	= microtime( true );

		$responseRemote = wp_remote_post( $this->url . $route, $params );
		$response 		= json_decode(
			wp_remote_retrieve_body( $responseRemote )
		);

		$time_post 	= microtime( true );
		$exec_time 	= round( ( $time_post - $time_pre ) * 1000 );
		
		$responseCode = ( !empty( $responseRemote['response']['code'] ) ) ? $responseRemote['response']['code'] : null ;

		if( $responseCode != 200 ) {
			// clearDataStored->clear();
		}
		
		if( empty( $response ) ) {
			return (object) [
				'success'	=> false,
				'errors'	=> ['Ocorreu um erro ao se conectar com a API do Melhor Envio'],
			];
		}

		if( !empty( $response->message ) && $response->message == 'Unauthenticated.') {
			# ( new SessionNoticeService())->add('Verificar seu token Melhor Envio');
			return (object) [
				'success'	=> false,
				'errors'	=> ['Usuário não autenticado'],
			];
		}

		# $errors = $this->treatmentErrors( $response );
		$errors = '';
		if( !empty( $errors ) ) {
			return (object) [
				'success'	=> false,
				'errors'	=> $errors,
			];
		}

		return $response;
	}

	
    /**
	 * Método que faz a requisição dos itens no carrinho melhor envio
	 * Obs: Devemos ter um método helper que vai distinguir os itens do carrinho para cada vendedor
     * 
	 * @param string $typeRequest
	 * @return object $response
     */
    public function requestCartItems( $typeRequest = 'GET' )
    {
		$params 	= array(
			'headers'	=> $this->headersCart,
			'method'	=> $typeRequest,
			'timeout'	=> self::TIMEOUT
		);
		$route 		= '/cart';
        $time_pre 	= microtime( true );

		$responseRemote = wp_remote_get( $this->url . $route, $params );
		$response 		= json_decode(
			wp_remote_retrieve_body( $responseRemote )
		);

		$time_post 	= microtime( true );
		$exec_time 	= round( ( $time_post - $time_pre ) * 1000 );
		
		$responseCode = ( !empty( $responseRemote['response']['code'] ) ) ? $responseRemote['response']['code'] : null ;

		if( $responseCode != 200 ) {
			// clearDataStored->clear();
		}
		
		if( empty( $response ) ) {
			return (object) [
				'success'	=> false,
				'errors'	=> ['Ocorreu um erro ao se conectar com a API do Melhor Envio'],
			];
		}

		if( !empty( $response->message ) && $response->message == 'Unauthenticated.') {
			# ( new SessionNoticeService())->add('Verificar seu token Melhor Envio');
			return (object) [
				'success'	=> false,
				'errors'	=> ['Usuário não autenticado'],
			];
		}

		# $errors = $this->treatmentErrors( $response );
		$errors = '';
		if( !empty( $errors ) ) {
			return (object) [
				'success'	=> false,
				'errors'	=> $errors,
			];
		}

		return $response;
    }


	/**
	 * Método responsável por fazer a busca de tickets gerados a partir de um termo passado como parâmetro
	 * 
	 * @param string $term
	 * @return object $request
	 */
	public function requestGet( $term, $route, $typeRequest = 'GET' )
	{
		$params 	= array(
			'headers'	=> $this->headersCart,
			'method'	=> $typeRequest,
			'timeout'	=> self::TIMEOUT
		);

        $time_pre 	= microtime( true );

		$responseRemote = wp_remote_get( $this->url . $route . $term, $params );
		$response 		= json_decode(
			wp_remote_retrieve_body( $responseRemote )
		);

		$time_post 	= microtime( true );
		$exec_time 	= round( ( $time_post - $time_pre ) * 1000 );
		
		$responseCode = ( !empty( $responseRemote['response']['code'] ) ) ? $responseRemote['response']['code'] : null ;

		if( $responseCode != 200 ) {
			// clearDataStored->clear();
		}
		
		if( empty( $response ) ) {
			return (object) [
				'success'	=> false,
				'errors'	=> ['Ocorreu um erro ao se conectar com a API do Melhor Envio'],
			];
		}

		if( !empty( $response->message ) && $response->message == 'Unauthenticated.') {
			# ( new SessionNoticeService())->add('Verificar seu token Melhor Envio');
			return (object) [
				'success'	=> false,
				'errors'	=> ['Usuário não autenticado'],
			];
		}

		# $errors = $this->treatmentErrors( $response );
		$errors = '';
		if( !empty( $errors ) ) {
			return (object) [
				'success'	=> false,
				'errors'	=> $errors,
			];
		}

		return $response;
	}

	/**
	 * Método para remover um ou mais itens do carrinho melhor envio
	 * 
	 * @param string $ticketId
	 * @param string $typeRequest
	 * 
	 * @return object
	 */
	public function requestDel( $ticketId, $typeRequest = 'DELETE' )
	{
		$params 	= array(
			'headers'	=> $this->headers,
			'method'	=> $typeRequest,
			'timeout'	=> self::TIMEOUT
		);
		$route 		= '/cart';
        $time_pre 	= microtime( true );

		$responseRemote = wp_remote_request( $this->url . $route . '/' . $ticketId, $params );
		$response 		= json_decode(
			wp_remote_retrieve_body( $responseRemote )
		);

		$time_post 	= microtime( true );
		$exec_time 	= round( ( $time_post - $time_pre ) * 1000 );
		
		$responseCode = ( !empty( $responseRemote['response']['code'] ) ) ? $responseRemote['response']['code'] : null ;

		if( !empty( $response->message ) && $response->message == 'Unauthenticated.') {
			# ( new SessionNoticeService())->add('Verificar seu token Melhor Envio');
			return (object) [
				'success'	=> false,
				'errors'	=> ['Usuário não autenticado'],
			];
		}

		if( empty( $response ) && $responseCode == 204 ) {
			return (object) [
				'success'	=> 'Removido com sucesso!',
				'errors'	=> false,
			];
		} else {
			return (object) [
				'success'	=> false,
				'errors'	=> $response->message,
			];
		}
	}

}
