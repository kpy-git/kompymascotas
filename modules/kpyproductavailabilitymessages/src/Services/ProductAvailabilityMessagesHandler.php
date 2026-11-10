<?php

declare(strict_types=1);

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Services;

use Db;
use PrestaShop\Module\KpyEvolutionPets\Config\Config;
use PrestaShop\Module\KpyOrderDispatcher\Service\WarehouseFinder;
use PrestaShop\Module\KpyProductAvailabilityMessages\Exception\KpyMessageStrategyNotFound;
use PrestaShop\Module\KpyProductAvailabilityMessages\Strategy\MessageStrategyManager;
use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartProductLazyArray;

class ProductAvailabilityMessagesHandler
{
    private ProductAvailabilityMessageFormatter $messageFormatter;

    public function __construct(
        private readonly WorkingDaysManager $workingDaysManager
    )
    {
        $this->messageFormatter = new ProductAvailabilityMessageFormatter();
    }

    public function getProductMessageInStock(int $productId, int $productAttributeId, int $idLang): string
    {
        if ($productAttributeId) {
            return Db::getInstance()->getValue(
                "SELECT available_now FROM " . _DB_PREFIX_ . "product_attribute_lang 
            WHERE id_product_attribute = $productAttributeId 
                AND id_lang = $idLang",
            ) ?: '';
        }

        return Db::getInstance()->getValue(
            "SELECT available_now FROM " . _DB_PREFIX_ . "product_lang 
            WHERE id_product = $productId 
                AND id_lang = $idLang",
        ) ?: '';
    }

    public function getProductMessageOutStock(int $productId, int $productAttributeId, int $idLang): string
    {
        if ($productAttributeId) {
            return Db::getInstance()->getValue(
                "SELECT available_later FROM " . _DB_PREFIX_ . "product_attribute_lang 
            WHERE id_product_attribute = $productAttributeId 
                AND id_lang = $idLang",
            ) ?: '';
        }

        return Db::getInstance()->getValue(
            "SELECT available_later FROM " . _DB_PREFIX_ . "product_lang 
            WHERE id_product = $productId 
                AND id_lang = $idLang",
        ) ?: '';
    }

    public function getMessageInStock(int $manufacturerId): string
    {
        $timestamps = $this->getInStockTimestampsRangeByManufacturer($manufacturerId);

        return $this->messageFormatter->convierteRangoTiempoADiasSemana($timestamps['start'], $timestamps['final']);
    }

    public function getInStockTimestampsRangeByManufacturer(int $manufacturerId): array
    {
        $distrivetBrands = json_decode(\Configuration::get('KPY_DISTRIVET_MANUFACTURERS'), true);
        $evolutionBrands = json_decode(\Configuration::get(Config::KPY_EVOLUTION_BRANDS), true);

        $limit = match (true) {
            in_array($manufacturerId, $evolutionBrands) => 8,
            in_array($manufacturerId, $distrivetBrands) => 6,
            default => 13,
        };

        $start = $this->workingDaysManager->isWorkingDay(time()) && (int)date('H') < $limit
            ? time() // se prepara el mismo día
            : $this->workingDaysManager->getNextWorkingDayTo(time()); // se prepara el día siguiente

        $start = $this->workingDaysManager->getNextWorkingDayTo($start);

        return [
            'start' => $start,
            'final' => $this->workingDaysManager->getNextWorkingDayTo($start)
        ];
    }

    public function getMessageOutStock(int $manufacturerId, int $productId, int $productAttributeId, int $lang = 1): string
    {
        $manualAvailabilityMessageByProduct = $this->getManualAvailabilityMessageByProduct($productId, $productAttributeId, $lang);

        if (!empty($manualAvailabilityMessageByProduct)) {
            return $manualAvailabilityMessageByProduct;
        }

        // mientras el stock no esté sincronizado todos los productos de Evolutions tienen la misma fecha tengan stock o no
        $evolutionBrands = json_decode(\Configuration::get(Config::KPY_EVOLUTION_BRANDS), true);
        if (in_array($manufacturerId, $evolutionBrands)) {
            return $this->getMessageInStock($manufacturerId);
        }

        $messageStrategyManager = new MessageStrategyManager();

        try {
            $deliveryRangeCalculator = new DeliveryRangeCalculator(
                $this->workingDaysManager,
                $messageStrategyManager->getAvailableStrategyByManufacturer($manufacturerId)
            );

            return $this->messageFormatter->convierteRangoTiempoADiasSemana(
                $deliveryRangeCalculator->getFirstDeliveryDay(),
                $deliveryRangeCalculator->getEndDeliveryDay(),
            );

        } catch (KpyMessageStrategyNotFound $exception) {
            return 'Disponible próximamente';
        }

    }

    /**
     * @throws KpyMessageStrategyNotFound
     */
    private function getOutStockTimestampByManufacturer(int $manufacturerId): int
    {
        // mientras el stock no esté sincronizado todos los productos de Evolutions tienen la misma fecha tengan stock o no
        $evolutionBrands = json_decode(\Configuration::get(Config::KPY_EVOLUTION_BRANDS), true);
        if (in_array($manufacturerId, $evolutionBrands)) {
            return $this->getInStockTimestampsRangeByManufacturer($manufacturerId)['start'];
        }

        $messageStrategyManager = new MessageStrategyManager();

        $deliveryRangeCalculator = new DeliveryRangeCalculator(
            $this->workingDaysManager,
            $messageStrategyManager->getAvailableStrategyByManufacturer($manufacturerId)
        );

        return $deliveryRangeCalculator->getFirstDeliveryDay();

    }

    public function getManualAvailabilityMessageByProduct(int $productId, int $productAttributeId, int $shop = 1): string
    {
        $sql = $productAttributeId > 0
            ? "SELECT available_date FROM " . _DB_PREFIX_ . "product_attribute_shop WHERE id_product_attribute = $productAttributeId AND id_shop = $shop"
            : "SELECT available_date FROM " . _DB_PREFIX_ . "product_shop WHERE id_product = $productId AND id_shop = $shop";
        $date = \Db::getInstance()->getValue($sql);


        if (empty($date) || time() > strtotime($date)) {
            return '';
        }

        return "Recíbelo a partir del " . $this->messageFormatter->getWeekdayAndMonthInText(\DateTimeImmutable::createFromFormat('Y-m-d', $date)->getTimestamp());
    }

    public function getMessageByCart(int $cart, array $products): string
    {
        $warehouseFinder = new WarehouseFinder();
        // si hay productos de más de un almacén siempre se prepara el pedido desde tienda, se añade una semana al día de hoy (hay que esperar a tener todos los productos en tienda)
        if ($warehouseFinder->hasMultiWarehouse($cart, $products)) {
            $start = $this->workingDaysManager->getNextWorkingDayTo(strtotime('+7 days'));

        } else {
            // si todos están en el mismo almacén se calcula la fecha más alta y se utiliza
            $start = 0;
            /** @var CartProductLazyArray $product */
            foreach ($products as $product) {
                try {
                    $startProduct = $product->quantity >= $product->cart_quantity + $product->quantity_wanted
                        ? $this->getInStockTimestampsRangeByManufacturer($product->id_manufacturer)['start']
                        : $this->getOutStockTimestampByManufacturer($product->id_manufacturer);
                } catch (KpyMessageStrategyNotFound $exception) {
                    // nunca debería ocurrir que un producto sin stock no tenga forma de calcular el mensaje, pero por si las moscas...
                    $startProduct = strtotime('+2 week');
                }

                $start = max($startProduct, $start);
            }
        }

        return "Recíbelo a partir del " . $this->messageFormatter->getWeekdayAndMonthInText($start);
    }
}