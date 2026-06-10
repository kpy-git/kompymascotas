<?php

namespace PrestaShop\Module\KpyAquaOrders\Config;

enum AquaOrderState: string
{
    case PREPARING = 'en_preparacion';
    case UPDATE_ORDER = 'actualizar_pedido';
    case UPDATE_ADDRESS = 'actualizar_direccion';
    case UPDATE_PRODUCTS = 'actualizar_productos';
    case RENAME_ORDER = 'renombrar_pedido';
    case RECARGO = 'recargo';
    case DELETE_ORDER = 'eliminar_pedido_para_liberar';
    case UNDO_PREPARING = 'deshacer_preparar';
    case NEW_ORDER = 'nuevo_pedido';

    case CANCEL_ORDER = 'cancelar_pedido';
    case CANCELLATION_REQUEST = 'solicitud_cancelacion';
    case CANCEL_CANCELLATION_REQUEST = 'solicitud_cancelacion_anulada';
    case PENDING_SYNC = 'pendiente_sincronizacion';
    case NEFTYS_ORDER = 'transmitido_neftys';

    public function isInstallable(): bool
    {
        return match ($this) {
            self::NEW_ORDER, self::CANCEL_ORDER, self::CANCEL_CANCELLATION_REQUEST, self::CANCELLATION_REQUEST, self::NEFTYS_ORDER  => false,
            default => true,
        };
    }

    public static function getInstallablesOrderState(): array
    {
        return array_filter(self::cases(), static fn (self $orderState) => $orderState->isInstallable());
    }

    public function getPrestaShopName(): string
    {
        return match($this) {
            self::PREPARING => 'AQUA - En preparación',
            self::UNDO_PREPARING => 'AQUA - Incidencia en preparación',
            self::UPDATE_ORDER => 'AQUA - Actualizar pedido completo',
            self::UPDATE_ADDRESS => 'AQUA - Actualizar dirección',
            self::UPDATE_PRODUCTS => 'AQUA - Cambio de productos',
            self::RENAME_ORDER => 'AQUA - Renombrar y volver a importar',
            self::DELETE_ORDER => 'AQUA - Pedido eliminado para liberar mercancía',
            self::RECARGO => 'AQUA - Recargo de equivalencia',
            self::PENDING_SYNC => 'AQUA - Pendiente de sincronizar',
            default => $this->value,
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PREPARING => '#fff330',
            self::UNDO_PREPARING => '#df6200',
            self::DELETE_ORDER => '#ffb35c',
            self::RECARGO => "#0099e0",
            self::PENDING_SYNC => '#EBB3B3',
            default => "#009980",
        };
    }
}
