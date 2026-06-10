<link rel="stylesheet" type="text/css"
	href="{$this_path|escape:'htmlall'}/views/templates/front/css/estilos.css" />

<div>
    <iframe id="ifrRedsysQR" name="ifrRedsysQR" frameborder="no" style="border: 0; padding: 0; display: none;"></iframe>
    <form action="{$urlQR}" method="post" id="frmRedsysPayment" name="frmRedsysPayment" target="ifrRedsysQR">
        <input type="hidden" name="Ds_SignatureVersion" value="{$Ds_SignatureVersion}" />
        <input type="hidden" name="Ds_MerchantParameters" value="{$Ds_MerchantParameters}" />
        <input type="hidden" name="Ds_Signature" value="{$Ds_Signature}" />
    </form>
</div>

<script>
    if (typeof OPC !== typeof undefined) {
        prestashop.on('opc-payment-getPaymentList-complete', () => initRedsysQR());
    } else if (typeof AppOPC !== typeof undefined) {
        $(document).on('opc-load-payment:completed', () => initRedsysQR());
    }

    window.addEventListener('load', function() {
        initRedsysQR();
    });

    function initRedsysQR(){
        if (jQuery.ui !== 'undefined'){
            $.getScript("{$this_path|escape:'htmlall'}/views/templates/front/js/jquery-ui.min.js");
            $('head').append( $('<link rel="stylesheet" type="text/css" />').attr('href', '{$this_path|escape:'htmlall'}/views/templates/front/css/\jquery-ui.min.css') );
        }
        $('form[action=tarjetaQR]').off('submit').on('submit', function(e){
            openQR();

            return false;
        });

        window.addEventListener('message', function(event) {
            const url_ok = '{$url_ok}'.replaceAll('&amp;', '&');
            const url_ko = '{$url_ko}'.replaceAll('&amp;', '&');
            if(event.data.status === "CLOSE") {
                closeQR();
                location.reload();
            }else if(event.data.status == "TEST"){
                //Bloque de prueba
                window.location.href = url_ok;
            }else if(event.data.status === "OK"){
                window.location.href = url_ok;
            }else if(event.data.status === "KO"){
                window.location.href = url_ko;
            }
        });
    }

    function openQR(){
    $('#ifrRedsysQR').dialog({
        title: "",
        autoOpen: true,
        minWidth: 400,
        minHeight: 630,
        modal: true,
        draggable: false,
        resizable: false,
        close: function(event, ui) { $(this).remove();},
    }).width(400).height(630);

    document.getElementById("frmRedsysPayment").submit();

    $('#ifrRedsysQR').siblings('div.ui-dialog-titlebar').css("display","none");
    $('#ifrRedsysQR').siblings('div.ui-dialog-titlebar').children("button").remove();

    $('#ifrRedsysQR').parents('div.ui-dialog').css("width","auto");
}

    function closeQR(){
        $('#ifrRedsysQR').dialog('close');
    }
    
</script>