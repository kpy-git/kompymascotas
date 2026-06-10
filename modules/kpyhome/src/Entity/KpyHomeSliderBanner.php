<?php

declare(strict_types=1);

namespace PrestaShop\Module\KpyHome\Entity;

use DateTimeImmutable;

class KpyHomeSliderBanner
{
    private int $id;

    private string $image;

    private string $description;

    private string $url;

    private int $position;

    private bool $active;

    private ?DateTimeImmutable $dateFrom;

    private ?DateTimeImmutable $dateTo;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): KpyHomeSliderBanner
    {
        $this->id = $id;
        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): KpyHomeSliderBanner
    {
        $this->image = $image;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): KpyHomeSliderBanner
    {
        $this->description = $description;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): KpyHomeSliderBanner
    {
        $this->url = $url;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): KpyHomeSliderBanner
    {
        $this->position = $position;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): KpyHomeSliderBanner
    {
        $this->active = $active;
        return $this;
    }

    public function getDateFrom(): ?DateTimeImmutable
    {
        return $this->dateFrom;
    }

    public function setDateFrom(?DateTimeImmutable $dateFrom): KpyHomeSliderBanner
    {
        $this->dateFrom = $dateFrom;
        return $this;
    }

    public function getDateTo(): ?DateTimeImmutable
    {
        return $this->dateTo;
    }

    public function setDateTo(?DateTimeImmutable $dateTo): KpyHomeSliderBanner
    {
        $this->dateTo = $dateTo;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'image' => $this->getImage(),
            'description' => $this->getDescription(),
            'url' => $this->getUrl(),
        ];
    }

}