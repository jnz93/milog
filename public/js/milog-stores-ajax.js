
function milogTicketRequest(el){

    let type    = el.attr('data-action'),
        orderId = el.attr('data-order-id'),
        storeId = el.attr('data-store-id');

    var body = {};
    body.action     = 'milog_store_service_request';
    body.nonce      = storeAjax.nonce;
    body.type       = type;
    body.orderId    = orderId;
    body.storeId    = storeId;

    jQuery.ajax({
        url: storeAjax.url,
        type: 'POST',
        data: body,
        beforeSend: function(xhr){
            console.log('carregando...');
            jQuery('#container-spinner').removeClass('disabled-spinner');
        },
        error: function( err ){
            console.log('[Milog Store Request Error]');
            console.log(err);
        }
    })
    .done( function( response ){
        jQuery('#container-spinner').addClass('disabled-spinner');

        switch (type) {
            case 'purchase-ticket':
                if( response == 'success' ){
                    alert('Compra realizada com sucesso!');
                    el.hide();
                    el.siblings('.before-paid').hide();
                    el.siblings('.after-paid').show();
                } else {
                    alert('Houve um problema ao comprar a etiqueta. Tente novamente!');
                }
                
                break;
        
            case 'print-ticket':
                window.open( response, '_blank').focus();
                break;

            case 'tracking-ticket':
                alert('Status: ' + response);
                break;

            case 'cancel-ticket':
                alert('Recurso desabilitado temporariamente.');
                break;

            default:
                break;
        }
        
    });
}

function tokenRequest( ){

    var body = {};
    body.action     = 'milog_token_request';
    body.nonce      = storeAjax.nonce;

    jQuery.ajax({
        url: storeAjax.url,
        type: 'POST',
        data: body,
        beforeSend: function(xhr){
            console.log('carregando...');
            jQuery('#container-spinner').removeClass('disabled-spinner');
        },
        error: function( err ){
            console.log('[Milog Store Request Error]');
            console.log(err);
        }
    })
    .done( function( response ){
        jQuery('#container-spinner').addClass('disabled-spinner');
        
        var message = '<p class="">Token obtido e salvo com sucesso!</p>';
        
        if( response != 1 ){
            message = '<p class="">Erro. Por favor, tente novamente mais tarde.</p>';
        }

        jQuery('#containerGetToken').hide();
        jQuery('#containerResult').append(message);
    });

}