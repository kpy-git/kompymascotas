<?php

namespace PrestaShop\Module\KpyProductSync\Query;

interface QueryInterface
{
    public function fetch(array $params = []): array;

    public function getName(): string;
}