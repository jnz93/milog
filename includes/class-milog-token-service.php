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