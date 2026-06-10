<?php

namespace PrestaShop\Module\KpyManufacturer\LandingBuilder;

use Context;
use Db;
use Module;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractLandingBuilder
{
    protected int $manufacturerId = 0;

    protected Module $module;

    protected Context $context;

    protected TranslatorInterface $translator;

    public function setManufacturerId(int $manufacturerId): self
    {
        $this->manufacturerId = $manufacturerId;
        return $this;
    }

    public function setModule(Module $module): self
    {
        $this->module = $module;
        return $this;
    }

    public function setContext(Context $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    abstract public function isRightManufacturer(int $manufacturerId): bool;

    abstract public function getTemplate(): string;

    abstract public function getSmartyVars(): array;

    abstract public function getStyleSheetsFiles(): array;

    abstract public function getScriptsFiles(): array;

    abstract public function includeSliderScripts(): bool;

    public function getTopComments(int $maxComments = 5): array
    {
        // no se puede usar un CTE con executeS (la primera palabra no es SELECT...)
        return Db::getInstance()->executeS(
            "SELECT DISTINCT pc.content, pc.customer_name, pc.grade
            FROM " . _DB_PREFIX_ . "product_comment pc                       
            WHERE pc.id_product IN (SELECT id_product 
                       FROM " . _DB_PREFIX_ . "product 
                       WHERE id_manufacturer = {$this->manufacturerId})
              AND pc.validate = 1
              AND pc.deleted = 0
              AND char_length(pc.content) > 60
              AND EXISTS (SELECT 1 FROM " . _DB_PREFIX_ . "customer c WHERE c.id_customer = pc.id_customer AND c.id_shop={$this->context->shop->id})
            ORDER BY pc.date_add DESC, pc.grade DESC
            LIMIT {$maxComments}"
        );
    }

    public function getAverageGradeComments(): float
    {
        return (float)Db::getInstance()->getValue(
            "WITH products AS (
                SELECT id_product
                FROM " . _DB_PREFIX_ . "product
                WHERE id_manufacturer = {$this->manufacturerId}
            )
            SELECT ROUND(AVG(pc.grade), 1) as average
            FROM " . _DB_PREFIX_ . "product_comment pc
            INNER JOIN products p ON p.id_product = pc.id_product
            WHERE pc.validate = 1
              AND pc.deleted = 0"
        );
    }
}