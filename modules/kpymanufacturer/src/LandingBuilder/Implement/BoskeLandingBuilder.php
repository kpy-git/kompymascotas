<?php

namespace PrestaShop\Module\KpyManufacturer\LandingBuilder\Implement;

use Category;
use Manufacturer;
use PrestaShop\Module\KpyManufacturer\LandingBuilder\AbstractLandingBuilder;

class BoskeLandingBuilder extends AbstractLandingBuilder
{
    public function isRightManufacturer(int $manufacturerId): bool
    {
        return 178 === $manufacturerId;
    }

    public function getTemplate(): string
    {
        return 'landing-boske.tpl';
    }

    public function getSmartyVars(): array
    {
        return [
            'module_img' => $this->module->getPathUri() . 'views/img/boske/',
            'boske_category_url' => $this->context->link->getCategoryLink((new Category(Manufacturer::getCategoryRelatedByManufacturer(78)))),
            'boske_category_low_grain' => $this->context->link->getCategoryLink((new Category(2199))),
            'boske_category_low_grain_cat' => $this->context->link->getCategoryLink((new Category(5826))),
            'boske_category_grain_free' => $this->context->link->getCategoryLink((new Category(5527))),
            'boske_category_clinical' => $this->context->link->getCategoryLink((new Category(5529))),
            'average_grade' => $this->getAverageGradeComments(),
            'comments' => $this->getTopComments(),
        ];
    }

    public function getStyleSheetsFiles(): array
    {
        return ['landing-boske',];
    }

    public function getScriptsFiles(): array
    {
        return [];
    }

    public function includeSliderScripts(): bool
    {
        return false;
    }
}