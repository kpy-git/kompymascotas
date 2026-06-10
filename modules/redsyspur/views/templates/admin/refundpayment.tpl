<script type="text/javascript">
    jQuery(document).ready(function ($) {

        $(document).on('click', '.btn-action-redsys-refund', function (e) {
            e.preventDefault();
            $('#refund-modal').modal();
        });

        $(document).on('click', '#refund-button', function (e) {
            e.preventDefault();

            if( document.getElementById("amountRefundModal").value == 0) {

                    addMessageRefund('warning', 'No se puede realizar una devolución por un valor nulo.');
                    return;
                }

            var button = $(e.target);
            var refundUrl = $('.btn-action-redsys-refund').prop('href');

            button.addClass('disabled');
            addMessageRefund('info', 'Procesando operación...');
            
            $.ajax({
                url: refundUrl,
                data: {
                    orderId: '{$orderId}',
                    redsysOrder: '{$redsysOrder}',
                    shippingAmount: '{$shippingAmount}',
                    amount: $('#amountRefundModal').val(),
                },
                type: "POST",
                dataType: 'json',
                success: function (data) {
                    button.removeClass('disabled');
                    document.getElementById("amountRefundModal").value = parseFloat(0).toFixed(2);

                    if (data.result == '0') {
                        document.getElementById("refund-button").innerHTML = 'Devolución fallida, ¿reintentar?';
                        document.getElementById("refund-button").classList.remove('btn-primary');
                        document.getElementById("refund-button").classList.add('btn-danger');

                        addMessageRefund('danger', '<b>ERROR</b> | ' + data.error);
                        return false;
                    }

                    addMessageRefund('success', 'La devolución se ha procesado con éxito.');

                    document.getElementById("refund-button").innerHTML = 'Devolución completada';
                    document.getElementById("refund-button").classList.remove('btn-primary');
                    document.getElementById("refund-button").classList.remove('btn-danger');
                    document.getElementById("refund-button").classList.add('btn-success');
                    
                    self.location.href = document.URL;
                },
                error: function (request, status, error){
                    button.removeClass('disabled');
                    document.getElementById("amountRefundModal").value = parseFloat(0).toFixed(2);

                    document.getElementById("refund-button").innerHTML = 'Error, ¿reintentar?';
                    document.getElementById("refund-button").classList.remove('btn-primary');
                    document.getElementById("refund-button").classList.add('btn-danger');

                    addMessageRefund('danger', 'Se ha producido un error interno.');
                }
            });
        });

        $(document).on('show.bs.modal','#refund-modal', function (e) {

            document.getElementById("refund-button").innerHTML = 'Realizar devolución';
            document.getElementById("refund-button").classList.remove('btn-success');
            document.getElementById("refund-button").classList.remove('btn-danger');
            document.getElementById("refund-button").classList.remove('btn-warning');
            document.getElementById("refund-button").classList.add('btn-primary');

            if ({$amountPaid} == 0.00) {
                document.getElementById("amountRefundModal").disabled = true;
                document.getElementById("refund-button").disabled = true;

                document.getElementById("amountRefundModal").value = parseFloat(0).toFixed(2);

                document.getElementById("paidRefundModal").classList.remove('badge-success');
                document.getElementById("paidRefundModal").classList.add('badge-warning');

                document.getElementById("refund-button").innerHTML = 'Nada que devolver';
                document.getElementById("refund-button").classList.remove('btn-primary');
                document.getElementById("refund-button").classList.add('btn-warning');
            }

            if ({$discountAmount} != 0.00) {
                addMessageRefund('info', 'Esta orden tiene aplicado un descuento de <b>{$discountAmount} €</b>.');
            }
        })

        document.getElementById("amountRefundModal").addEventListener("change", function() {
            let val = parseFloat(this.value);

            if (val < 0) {
                this.value = 0;
            } else {
                if (val > {$amountPaid}) this.value = {$amountPaid};
            }

            this.value = parseFloat(this.value).toFixed(2);
        });

    });

    function addMessageRefund(type, text){

        var html = '<div class="alert alert-' + type + ' d-print-none" role="alert">' +
                    '   <button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '        <span aria-hidden="true"><i class="material-icons">close</i></span>' +
                    '    </button>' +
                    '    <div class="alert-text">' +
                    '        <p>' + text + '</p>' +
                    '    </div>' +
                    '</div>';

        $('#refund-messages .alert').remove();
        $('#refund-messages').append(html);

    }
</script>

<div id="refund-modal" class="modal fade"  tabindex="-1" role="dialog" aria-labelledby="modal_title">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title" id="modal_title">
                    <strong class="text-muted">#{$orderId}</strong>
                    <strong>{$reference}</strong>
                    <span style="font-weight: lighter;"> | DEVOLUCIÓN ONLINE VÍA REDSYS | </span><span>{$redsysOrder}</span>
                </h1>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="refund-messages"></div>
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <p>
                            <span class="badge badge-success rounded" id="paidRefundModal" style="font-size: 12px;min-width: 70px;display: inline-block">{$amountPaid} €</span> <span style="padding-left: 0.5rem;vertical-align: middle;">
                                Valor actual de la orden, aplicando descuentos y devoluciones realizadas.
                            </span>
                        </p>
                        <p>
                            <span class="badge badge-dark rounded" id="productsRefundModal"style="font-size: 12px;min-width: 70px;display: inline-block">{$productsAmount} €</span> <span style="padding-left: 0.5rem;vertical-align: middle;">
                                Importe de los productos.
                            </span>
                            <br>
                            <span class="badge badge-dark rounded" id="shippingRefundModal"style="font-size: 12px;min-width: 70px;display: inline-block">{$shippingAmount} €</span> <span style="padding-left: 0.5rem;vertical-align: middle;">
                                Importe del envío.
                            </span>
                        </p>
                        <p style="text-align: justify;">El importe introducido se enviará al TPV Virtual para procesar una devolución. El tiempo que puede pasar hasta que el titular reciba el reembolso en su cuenta bancaria dependerá de su entidad, y puede variar entre 2 y 31 días.</p>
                        <label for="amountRefundModal" class="control-label">
                            Importe a devolver:
                        </label>
                        <div class="" style="padding-right: 0.3rem">
                            <input type="number" step="0.01" class="form-control" id="amountRefundModal" name="amountRefundModal" value="" min="0" max="{$amountPaid}"/>
                        </div>
                        <br>
                        <p style="text-align: justify; color: gray;"><i><b>El estado de la orden no se actualizará automáticamente:</b> Deberás utilizar el sistema de devoluciones de Prestashop para devolver el stock al inventario y que la orden se marque como devuelta en tu backoffice.</i></p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-close" data-dismiss="modal">
                    Cerrar
                </button>
                <button id="refund-button" type="button" class="btn btn-primary">
                    Realizar devolución
                </button>
            </div>
        </div>
    </div>
</div>