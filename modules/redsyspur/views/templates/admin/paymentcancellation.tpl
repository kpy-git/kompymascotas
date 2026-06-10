<script type="text/javascript">
    jQuery(document).ready(function ($) {
        var shippingPaid = {$shippingPaid};

        $(document).on('click', '.btn-action-redsys-cancellation', function (e) {
            e.preventDefault();
            $('#cancellation-modal').modal();
        });

        $(document).on('click', '#cancellation-button', function (e) {
            e.preventDefault();

//			if( document.getElementById("amountCancellationModal").value == 0 ) {
//				addMessageCancellation('warning', 'No se puede realizar una anulación por un valor nulo.');
//				return;
//			}

            var button = $(e.target);
            var cancellationURL = $('.btn-action-redsys-cancellation').prop('href');
            
            button.addClass('disabled');
            addMessageCancellation('info', 'Procesando operación...');
            
            $.ajax({
                url: cancellationURL,
                data: {
                    orderId: '{$orderId}',
                    redsysOrder: '{$redsysOrder}',
                    shippingPaid: '{$shippingPaid}',
                    amount: {$grandTotal},
                },
                type: "POST",
                dataType: 'json',
                success: function (data) {
                    button.removeClass('disabled');
                    document.getElementById("amountCancellationModal").value = parseFloat(0).toFixed(2);

                    if (data.result == '0') {
                        document.getElementById("cancellation-button").innerHTML = 'Anulación fallida, ¿reintentar?';
                        document.getElementById("cancellation-button").classList.remove('btn-primary');
                        document.getElementById("cancellation-button").classList.add('btn-danger');

                        addMessageCancellation('danger', '<b>ERROR</b> | ' + data.error);
                        return false;
                    }

                    addMessageCancellation('success', 'La anulación se ha procesado con éxito.');

                    document.getElementById("cancellation-button").innerHTML = 'Anulación completada';
                    document.getElementById("cancellation-button").classList.remove('btn-primary');
                    document.getElementById("cancellation-button").classList.remove('btn-danger');
                    document.getElementById("cancellation-button").classList.add('btn-success');
                    
                    self.location.href = document.URL;
                },
                error: function (request, status, error){
                    button.removeClass('disabled');
                    
                    document.getElementById("cancellation-button").innerHTML = 'Error, ¿reintentar?';
                    document.getElementById("cancellation-button").classList.remove('btn-primary');
                    document.getElementById("cancellation-button").classList.add('btn-danger');

                    addMessageCancellation('danger', 'Se ha producido un error interno.');
                }
            });
        });

        $(document).on('show.bs.modal','#cancellation-modal', function (e) {

            // El SIS sólo permite anular totalmente una orden, enviar cualquier otro importe no anulara parcialmente, sino que seguira anulando la orden completa.
            document.getElementById("amountCancellationModalGroup").hidden = true;

            document.getElementById("cancellation-button").innerHTML = 'Realizar anulación';
            document.getElementById("cancellation-button").classList.remove('btn-success');
            document.getElementById("cancellation-button").classList.remove('btn-danger');
            document.getElementById("cancellation-button").classList.remove('btn-warning');
            document.getElementById("cancellation-button").classList.add('btn-primary');
			
			if ({$amountPaid} == 0.00) {
                document.getElementById("paidCancellationModal").classList.remove('badge-success');
                document.getElementById("paidCancellationModal").classList.add('badge-dark');
            }

            if ({$remainingAmount} == 0.00) {
                document.getElementById("amountCancellationModal").disabled = true;
                document.getElementById("cancellation-button").disabled = true;

                document.getElementById("amountCancellationModal").value = parseFloat(0).toFixed(2);

                document.getElementById("remainingCancellationModal").classList.remove('badge-warning');
                document.getElementById("remainingCancellationModal").classList.add('badge-dark');

                document.getElementById("cancellation-button").innerHTML = 'Nada que anular';
                document.getElementById("cancellation-button").classList.remove('btn-primary');
                document.getElementById("cancellation-button").classList.add('btn-warning');
				
				if ({$amountPaid} == 0.00) {
					document.getElementById("paidCancellationModal").classList.remove('badge-dark');
					document.getElementById("paidCancellationModal").classList.add('badge-warning');
				}
            }

            if ({$discountAmount} != 0.00) {
                addMessageRefund('info', 'Esta orden tiene aplicado un descuento de <b>{$discountAmount} €</b>.');
            }
        })

        document.getElementById("amountCancellationModal").addEventListener("change", function() {
            let val = parseFloat(this.value);

            if (val < 0) this.value = 0;
            if (val > {$remainingAmount}) this.value = {$remainingAmount};

            this.value = parseFloat(this.value).toFixed(2);
        });

    });

    function addMessageCancellation(type, text){

        var html = '<div class="alert alert-' + type + ' d-print-none" role="alert">' +
                    '   <button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '        <span aria-hidden="true"><i class="material-icons">close</i></span>' +
                    '    </button>' +
                    '    <div class="alert-text">' +
                    '        <p>' + text + '</p>' +
                    '    </div>' +
                    '</div>';

        $('#cancellation-messages .alert').remove();
        $('#cancellation-messages').append(html);

    }
</script>

<div id="cancellation-modal" class="modal fade"  tabindex="-1" role="dialog" aria-labelledby="modal_title">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title" id="modal_title">
                    <strong class="text-muted">#{$orderId}</strong>
                    <strong>{$reference}</strong>
                    <span style="font-weight: lighter;"> | ANULACIÓN DE LA ORDEN | </span><span>{$redsysOrder}</span>
                </h1>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="cancellation-messages"></div>
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <p>
                            <span class="badge badge-success rounded" id="paidCancellationModal" style="font-size: 12px;min-width: 70px;display: inline-block">{$amountPaid} €</span> <span style="padding-left: 0.5rem;vertical-align: middle;">
                                Valor actual de la orden, aplicando descuentos y devoluciones realizadas.
                            </span>
							<br>
							<span class="badge badge-warning rounded" id="remainingCancellationModal"style="font-size: 12px;min-width: 70px;display: inline-block">{$remainingAmount} €</span> <span style="padding-left: 0.5rem;vertical-align: middle;">
                                Importe pendiente de confirmar.
                            </span>

                        </p>
                        <p>
                            <span class="badge badge-dark rounded" id="cancelledCancellationModal"style="font-size: 12px;min-width: 70px;display: inline-block">{$cancellationAmount} €</span> <span style="padding-left: 0.5rem;vertical-align: middle;">
                                Importe anulado.
                            </span>
                            <br>
                            <span class="badge badge-dark rounded" id="refundedCancellationModal"style="font-size: 12px;min-width: 70px;display: inline-block">{$refundAmount} €</span> <span style="padding-left: 0.5rem;vertical-align: middle;">
                                Importe devuelto.
                            </span>
                        </p>
                        <p style="text-align: justify;">Las anulaciones suelen procesarse de manera instantánea. Ten en cuenta que la orden se anulará por completo y el importe íntegro se devolverá al cliente.</p>
                        <div class="" style="padding-right: 0.3rem" id="amountCancellationModalGroup">
                        <label for="amountCancellationModal" class="control-label" id="amountCancellationModalLabel">
                            Importe de la anulación:
                        </label>
                            <input type="number" step="0.01" class="form-control" id="amountCancellationModal" name="amountCancellationModal"  value="{$grandTotal}" min="0" max="{$grandTotal}"/>
                            <br>
                        </div>
                        <p style="text-align: justify; color: gray;"><i>Recuerde que deberá utilizar el sistema de devoluciones de Prestashop para marcar como anulada la orden, usando las opciones de reembolso parcial o estándar y así devolver el stock al inventario.</i></p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-close" data-dismiss="modal">
                    Cerrar
                </button>
                <button id="cancellation-button" type="button" class="btn btn-primary">
                    Realizar anulación
                </button>
            </div>
        </div>
    </div>
</div>