
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
        },
        error: function( err ){
            console.log('[Milog Store Request Error]');
            console.log(err);
        }
    })
    .done( function( response ){
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