<?php

declare(strict_types=1);

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Services;

use Db;

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

    public function getMessageInStock(): string
    {
        $start = (int)date('H') < 12 ? time() : $this->workingDaysManager->getNextWorkingDayTo(time());

        $start = $this->workingDaysManager->getNextWorkingDayTo($start);
        $final = $this->workingDaysManager->getNextWorkingDayTo($start);

        return $this->messageFormatter->convierteRangoTiempoADiasSemana($start, $final);
    }

    public function getMessageOutStock(int $manufacturerId, int $productId, int $productAttributeId, int $lang = 1): string
    {
        $manualAvailabilityMessageByProduct = $this->getManualAvailabilityMessageByProduct($productId, $productAttributeId, $lang);

        if (!empty($manualAvailabilityMessageByProduct)) {
            return $manualAvailabilityMessageByProduct;
        }

        $groupA = [3, 77, 78, 75, 48, 49, 13, 203, 121, 58]; // RC, Dingo, Acana, Orijen , TOW, ANC Fresh
        $groupB = [93, 173,]; // Natural Greatness, Alpha Spirit
        $montilla = [4, 27, 199]; // Advance, Libra, Natures Variety

        if (!in_array($manufacturerId, array_merge($groupA, $groupB), true)) {
            return 'Disponible próximamente';
        }

        if (in_array($manufacturerId, $groupA, true)) {
            // si es antes de las 11 se puede hacer el pedido el mismo día, si no el siguiente laborable
            $start = $this->workingDaysManager->isWorkingDay(time()) && (int)date('H') < 11
                ? time()
                : $this->workingDaysManager->getNextWorkingDayTo(time());

            // + 2 días en venir la mercancía + 1 día de envío
            $start = $this->workingDaysManager->addWorkingDaysToTimestamp($start, 3);
            $final = $this->workingDaysManager->getNextWorkingDayTo($start);

            return $this->messageFormatter->convierteRangoTiempoADiasSemana($start, $final);
        }

        // ANC Fresh
        if ($manufacturerId === 203) {
            $start = $this->workingDaysManager->isWorkingDay(time()) && (int)date('H') < 11
                ? time()
                : $this->workingDaysManager->getNextWorkingDayTo(time());

            // + 3 días en venir la mercancía + 1 día de envío
            $start = $this->workingDaysManager->addWorkingDaysToTimestamp($start, 4);
            $final = $this->workingDaysManager->getNextWorkingDayTo($start);

            return $this->messageFormatter->convierteRangoTiempoADiasSemana($start, $final);
        }

        if (in_array($manufacturerId, $montilla, true)) {
            // si es antes de las 11 se puede hacer el pedido el mismo día, si no el siguiente laborable
            $start = $this->workingDaysManager->isWorkingDay(time()) && (int)date('H') < 11
                ? time()
                : $this->workingDaysManager->getNextWorkingDayTo(time());

            // + 4 días en venir la mercancía + 1 día de envío
            $start = $this->workingDaysManager->addWorkingDaysToTimestamp($start, 5);
            $final = $this->workingDaysManager->getNextWorkingDayTo($start);

            return $this->messageFormatter->convierteRangoTiempoADiasSemana($start, $final);
        }

        // groupB
        // si es lunes antes de la 10 se puede hacer el pedido hoy, si no el siguiente lunes
        $start = (int)date('N') === 1 && (int)date('H') < 11 ? time() : strtotime('next Monday');

        if (!$this->workingDaysManager->isWorkingDay($start)) {
            $start = $this->workingDaysManager->getNextWorkingDayTo($start);
        }

        // + 1 día en venir la mercancía + 1 día de envío
        $start = $this->workingDaysManager->addWorkingDaysToTimestamp($start, 2);
        $final = $this->workingDaysManager->getNextWorkingDayTo($start);

        return $this->messageFormatter->convierteRangoTiempoADiasSemana($start, $final);

    }

    public function getManualAvailabilityMessageByProduct(int $productId, int $productAttributeId, int $lang = 1): string
    {
        $sql = $productAttributeId > 0
            ? "SELECT available_later FROM " . _DB_PREFIX_ . "product_attribute_lang WHERE id_product_attribute = $productAttributeId AND id_lang = $lang"
            : "SELECT available_later FROM " . _DB_PREFIX_ . "product_lang WHERE id_product = $productId AND id_lang = $lang";
        $date = \Db::getInstance()->getValue($sql);


        if (empty($date)) {
            return '';
        }

        return "Recíbelo a partir de el " . $this->messageFormatter->getWeekdayAndMonthInText(\DateTimeImmutable::createFromFormat('Y-m-d', $date)->getTimestamp());
    }
}