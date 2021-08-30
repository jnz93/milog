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
	const URL  			= 'https://api.melhorenvio.com/v2/me';
	const SANDBOX_URL 	= 'https://sandbox.melhorenvio.com.br/api/v2/me';
	const TIMEOUT 		= 10;

	# Constant com limite de tempo para uma requisição HTTP, se passar disso, um log dessa requisição será gerado
	const TIME_LIMIT_LOG_REQUEST = 1000;

	protected $token;
	protected $headers;
	protected $headersCompanies;
	protected $url;

	public function __construct()
	{
		$this->token 	= 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImE3YzVkYTE2NGNmMDU3NjlhYTZiNzFkNzc4MDk1ZGUxODlkYzgxOGI2NmM0YmE3MmZmNzBjMmMxZWQzNWZkYzBhNThhNjFiZjNkZGJjNjk1In0.eyJhdWQiOiI5NTYiLCJqdGkiOiJhN2M1ZGExNjRjZjA1NzY5YWE2YjcxZDc3ODA5NWRlMTg5ZGM4MThiNjZjNGJhNzJmZjcwYzJjMWVkMzVmZGMwYTU4YTYxYmYzZGRiYzY5NSIsImlhdCI6MTYyNzU4NDU3NywibmJmIjoxNjI3NTg0NTc3LCJleHAiOjE2NTkxMjA1NzcsInN1YiI6IjlmZDdhMDZjLWNlYmMtNDQ1Ny05ZGJlLTNjYTExOTg3OTg4MSIsInNjb3BlcyI6WyJjYXJ0LXJlYWQiLCJjYXJ0LXdyaXRlIiwiY29tcGFuaWVzLXJlYWQiLCJjb21wYW5pZXMtd3JpdGUiLCJjb3Vwb25zLXJlYWQiLCJjb3Vwb25zLXdyaXRlIiwibm90aWZpY2F0aW9ucy1yZWFkIiwib3JkZXJzLXJlYWQiLCJwcm9kdWN0cy1yZWFkIiwicHJvZHVjdHMtZGVzdHJveSIsInByb2R1Y3RzLXdyaXRlIiwicHVyY2hhc2VzLXJlYWQiLCJzaGlwcGluZy1jYWxjdWxhdGUiLCJzaGlwcGluZy1jYW5jZWwiLCJzaGlwcGluZy1jaGVja291dCIsInNoaXBwaW5nLWNvbXBhbmllcyIsInNoaXBwaW5nLWdlbmVyYXRlIiwic2hpcHBpbmctcHJldmlldyIsInNoaXBwaW5nLXByaW50Iiwic2hpcHBpbmctc2hhcmUiLCJzaGlwcGluZy10cmFja2luZyIsImVjb21tZXJjZS1zaGlwcGluZyIsInRyYW5zYWN0aW9ucy1yZWFkIiwidXNlcnMtcmVhZCIsInVzZXJzLXdyaXRlIiwid2ViaG9va3MtcmVhZCIsIndlYmhvb2tzLXdyaXRlIl19.EgJDvhQOEXmPM_-tfme1w_g2F9DZDfHyjixHoE6iPqMbTdD_yNwSW9XaQtXGoRI-pLtSTfhVWzJbrVvo_4TuJj9AzKC3FEKC5ikDHpe8BAOKQ8lAJUtr1ySg85ew7d42kqEJpUf01XCzW_tUnzBxFKMzpB2D9ofZpd77_tJ0BHoRtS7vVdnGa1Olrc4cMt9aHHPiVRrDKnxXJUGKCVWOv173G1-7OP98ow9w6KuGsWHSXFd5_5-m-PvvObskfVlzc-Ccs3sg23UEZpYjUvnUkETWMAYmGQfJMMHCGRmoCA3I6yoy5q9aV__LnvI1A2x7aHQvOpUmoIVAPw-XwmAjNgpiqFhPve05n5o7IcQVdgXXKLHahPMWyz2Y-K4W_gCsrpglwZVLV-Y2k1BRUVGckD5YCgasq86qQnA2qWch_yluOHNmUdFi3CwAD-KDaQlhSSscnevjg62zcmDQ06mbetwKP1x7loeLsPRmC0tqKU_yzSaV5LR7P8h6C4o77EgkmiqCuBKDiSiYLDaAUaGdixPxPNE1dvWKwDHTqzaHtZBHv_0qT5o0qakuGXZQOKSV63m0esH6ioEVgkBjd6c9S0JnRPsOcx2bZuZFER75pKcI7Oee5gfNlEmdoQqjTIdHIPNxuSk3inZ-KrOGReEFYAHhitStPMy62rjHubeTJiI';
		$this->url 		= self::SANDBOX_URL;
		$this->headers 	= array(
			'Accept'		=> 'application/json',
			'Content-Type'	=> 'application/json',
			'Authorization'	=> 'Bearer ' . $this->token,
			'User-Agent'	=> 'Mercado Indústria logs@unitycode.tech'
		);
		$this->headersCompanies = array(
			'User-Agent' 	=> 'Mercado Indústria logs@unitycode.tech'
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

		$params 	= array(
			// 'headers'	=> $this->headers,
			'method'	=> $typeRequest,
			'body'		=> $body,
			'timeout'	=> self::TIMEOUT
		);

		$time_pre 	= microtime( true );

		$responseRemote = wp_remote_request( $this->url . $route );
		$response 		= json_decode(
			wp_remote_retrieve_body( $responseRemote )
		);

		echo '<pre>';
		print_r($responseRemote);
		echo '</pre>';
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
