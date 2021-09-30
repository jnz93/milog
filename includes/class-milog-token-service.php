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
class Milog_Token_Service{

    protected $requestService;

    public function __construct(){
        // $this->requestService   = new Milog_Request_Service();
    }
    
    /**
     * Metodo responsável por solicitar o token de acesso. 
     * Será retornado um array contendo o tipo do token, o tempo de expiração, o token de acesso e o refresh token;
     *  
     */
    public function getDataToken()
    {
        $authCode = get_option( '_me_auth_code' );
        if( ! $authCode ){
            return 'Código de autorização inválido!';
        }

        $route          = '/oauth/token';
        $typeRequest    = 'POST';
        $clientId       = 2271;
        $clientSecret   = 'sUAL9h7J8CuDDJHBSN1RfbWbZQmvj7vzToxhgQaY';
        $redirectUri    = 'https://mercadoindustria.com.br/autorizacao-melhor-envio';

        $body = array(
            'grant_type'    => 'authorization_code',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'code'          => $authCode
        );
        $request = $this->requestService->requestAuth( $route, $typeRequest, $body );
        $body = json_encode( $body, JSON_PRETTY_PRINT );
        $request = json_encode( $request, JSON_PRETTY_PRINT );
        
        return $request;
    }

    /**
     * Método responsável por salvar o token e dados retornados da requisição getDataToken()
     * 
     * @param object/mixed $data
     * @return bool 
     */
    public function saveToken( $data )
    {
        $key            = '_milog_data_token';
        $isSaved        = update_option( $key, $data );

        return $isSaved;
    }

    /**
     * Método responsável por retornar os dados relacionados ao token salvo
     * 
     */
    public function getData(){
        $key    = '_milog_data_token';
        $data   = get_option( $key );
        $dataToObj = json_decode( $data );

        return $dataToObj;
    }

    /**
     * Método responsável por retornar o token de acesso salvo
     * 
     */
    public function getToken()
    {
        $data = $this->getData();
        return $data->access_token;
    }

    /**
     * Método responsável por retornar o refresh_token
     * 
     */
    public function getRefreshToken()
    {
        $data = $this->getData();
        return $data->refresh_token;
    }

    /**
     * Método responsável por retornar o tempo de expiração do token ativo
     * 
     */
    public function getExperationToken()
    {
        $data = $this->getData();
        return $data->expires_in;
    }

    /**
     * Método responsável por retornar o tipo do token
     * 
     */
    public function getTypeToken()
    {
        $data = $this->getData();
        return $data->token_type;
    }
}