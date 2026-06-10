<script type="text/javascript">
    jQuery(document).ready(function ($) {
        var shippingPaid = {$shippingPaid};

        $(document).on('click', '.btn-action-redsys-confirmation', function (e) {
            e.preventDefault();
            $('#confirmation-modal').modal();
        });

        $(document).on('click', '#confirmation-button', function (e) {
            e.preventDefault();

//			if( document.getElementById("amountConfirmationModal").value == 0 ) {
//				addMessageConfirmation('warning', 'No se puede realizar una confirmación por un valor nulo.');
//				return;
//			}

            var button = $(e.target);
            var confirmationURL = $('.btn-action-redsys-confirmation').prop('href');

            button.addClass('disabled');
            addMessageConfirmation('info', 'Procesando operación...');
            
            $.ajax({
                url: confirmationURL,
                data: {
                    orderId: '{$orderId}',
                    redsysOrder: '{$redsysOrder}',
                    shippingPaid: '{$shippingPaid}',
                    transactionType: '{$transactionType}',
                    shippingConfirmed: document.querySelector('#shipmentStatusConfirmationModal').checked,
                    amount: {$remainingAmount},
                },
                type: "POST",
                dataType: 'json',
                success: function (data) {
                    button.removeClass('disabled');
                    document.getElementById("amountConfirmationModal").value = parseFloat(0).toFixed(2);
                    document.getElementById("shipmentStatusConfirmationModal").checked = false;

                    if (data.result == '0') {
                        document.getElementById("confirmation-button").innerHTML = 'Confirmación fallida, ¿reintentar?';
                        document.getElementById("confirmation-button").classList.remove('btn-primary');
                        document.getElementById("confirmation-button").classList.add('btn-danger');

                        addMessageConfirmation('danger', '<b>ERROR</b> | ' + data.error);
                        return false;
                    }

                    addMessageConfirmation('success', 'La confirmación se ha procesado con éxito.');

                    document.getElementById("confirmation-button").innerHTML = 'Confirmación completada';
                    document.getElementById("confirmation-button").classList.remove('btn-primary');
                    document.getElementById("confirmation-button").classList.remove('btn-danger');
                    document.getElementById("confirmation-button").classList.add('btn-success');
                    
                    self.location.href = document.URL;
                },
                error: function (request, status, error){
                    button.removeClass('disabled');
                    document.getElementById("shipmentStatusConfirmationModal").checked = false;

                    document.getElementById("confirmation-button").innerHTML = 'Error, ¿reintentar?';
                    document.getElementById("confirmation-button").classList.remove('btn-primary');
                    document.getElementById("confirmation-button").classList.add('btn-danger');

                    addMessageConfirmation('danger', 'Se ha producido un error interno.');
                }
            });
        });

        $(document).on('show.bs.modal','#confirmation-modal', function (e) {

            // De momento sólo se permiten anulaciones totales.
            document.getElementById("amountConfirmationModalGroup").hidden = true;

            document.getElementById("confirmation-button").innerHTML = 'Realizar confirmación';
            document.getElementById("confirmation-button").classList.remove('btn-success');
            document.getElementById("confirmation-button").classList.remove('btn-danger');
            document.getElementById("confirmation-button").classList.remove('btn-warning');
            document.getElementById("confirmation-button").classList.add('btn-primary');

            if (shippingPaid) {
                document.getElementById("shipmentStatusConfirmationModal").disabled = true;
            }
			
			if ({$amountPaid} == 0.00) {
                document.getElementById("paidConfirmationModal").classList.remove('badge-success');
                document.getElementById("paidConfirmationModal").classList.add('badge-dark');
            }

            if ({$remainingAmount} == 0.00) {
                document.getElementById("amountConfirmationModal").disabled = true;
                document.getElementById("confirmation-button").disabled = true;

                document.getElementById("amountConfirmationModal").value = parseFloat(0).toFixed(2);

                document.getElementById("remainingConfirmationModal").classList.remove('badge-warning');
                document.getElementById("remainingConfirmationModal").classList.add('badge-dark');

                document.getElementById("confirmation-button").innerHTML = 'Nada por confirmar';
                document.getElementById("confirmation-button").classList.remove('btn-primary');
                document.getElementById("confirmation-button").classList.add('btn-warning');
				
				if ({$amountPaid} == 0.00) {
					document.getElementById("paidConfirmationModal").classList.remove('badge-dark');
					document.getElementById("paidConfirmationModal").classList.add('badge-warning');
				}
            }
            
            if ({$discountAmount} != 0.00) {
                addMessageRefund('info', 'Esta orden tiene aplicado un descuento de <b>{$discountAmount} €</b>.');
            }
        })

        document.getElementById("amountConfirmationModal").addEventListener("change", function() {
            let val = parseFloat(this.value);

            if (val < 0) this.value = 0;
            this.value = parseFloat(this.value).toFixed(2);            
        });

    });

    function addMessageConfirmation(type, text){

        var html = '<div class="alert alert-' + type + ' d-print-none" role="alert">' +
                    '   <button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '        <span aria-hidden="true"><i class="material-icons">close</i></span>' +
                    '    </button>' +
                    '    <div class="alert-text">' +
                    '        <p>' + text + '</p>' +
                    '    </div>' +
                    '</div>';

        $('#confirmation-messages .alert').remove();
        $('#confirmation-messages').append(html);

    }
</script>

<div id="confirmation-modal" class="modal fade"  tabindex="-1" role="dialog" aria-labelledby="modal_title">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title" id="modal_title">
                    <strong class="text-muted">#{$orderId}</strong>
                    <strong>{$reference}</strong>
                    <span style="font-weight: lighter;"> | CONFIRMACIÓN DE LA ORDEN | </span><span>{$redsysOrder}</span>
                </h1>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="confirmation-messages"></div>
                <form class="form-horizontal" role="form">
                    <div class="">
                        <p>
                            <span class="badge badge-success rounded" id="paidConfirmationModal" style="font-size: 12px;min-width: 70px;display: inline-block">{$amountPaid} €</span> <span style="padding-left: 0.5rem;vertical-align: middle;">
                                Valor actual de la orden, aplicando descuentos y devoluciones realizadas.
                            </span>
							<br>
							<span class="badge badge-warning rounded" id="remainingConfirmationModal"style="font-size: 12px;min-width: 70px;display: inline-block">{$remainingAmount} €</span> <span style="padding-left: 0.5rem;vertical-align: middle;">
                                Importe pendiente de confirmar.
                            </span>
                        </p>
                        <p>
                            <span class="badge badge-dark rounded" id="cancelledConfirmationModal"style="font-size: 12px;min-width: 70px;display: inline-block">{$cancellationAmount} €</span> <span style="padding-left: 0.5rem;vertical-align: middle;">
                                Importe anulado.
                            </span>
                            <br>
                            <span class="badge badge-dark rounded" id="refundedConfirmationModal"style="font-size: 12px;min-width: 70px;display: inline-block">{$refundAmount} €</span> <span style="padding-left: 0.5rem;vertical-align: middle;">
                                Importe devuelto.
                            </span>
                        </p>
                        <p style="text-align: justify;">La cofirmación cobrará el importe de la orden al cliente y marcará la orden como completada. Una vez hecho, podrás realizar una devolución usando la opción de "Devolución vía Redsys".</p>
                        <div id="amountConfirmationModalGroup">
                            <p style="text-align: justify;">Si la confirmación que realizas no es total, es decir, que confirmas sólo una parte del importe preautorizado, <b>se liberará el importe restante</b> y se devolverá al cliente.</p>
                            <label for="amountConfirmationModal" class="control-label">
                                Importe de la confirmación:
                            </label>
                            <div class="" style="padding-right: 0.3rem">
                                <input type="number" step="0.01" class="form-control" id="amountConfirmationModal" name="amountConfirmationModal" value="{$remainingAmount}" min="0" max="{$remainingAmount}"/>
                            </div>
                            <br>
                            <div class="checkbox">
                                <div class="md-checkbox md-checkbox-inline">
                                <label>    
                                    <input type="checkbox" id="shipmentStatusConfirmationModal" name="shipmentStatusConfirmationModal" checked>
                                        <i class="md-checkbox-control">
                                        </i>
                                    ¿La confirmación incluye el importe del envío?
                                </label>
                                </div>
                            </div>
                            <br>
                            <p style="text-align: justify; color: gray;"><i>Informar si la confirmación incluye el coste del envío no tiene ningún tipo de implicación en la facturación, sólamente se utilizará para presentar correctamente la información en futuras operaciones sobre esta misma orden.</i></p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-close" data-dismiss="modal">
                    Cerrar
                </button>
                <button id="confirmation-button" type="button" class="btn btn-primary">
                    Realizar confirmación
                </button>
            </div>
        </div>
    </div>
</div>