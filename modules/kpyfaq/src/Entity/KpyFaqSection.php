<?php

namespace PrestaShop\Module\KpyFaq\Entity;

class KpyFaqSection
{
    private int $id;

    private string $title;

    private string $image;

    /** @var KpyFaqElement[] */
    public array $elements = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getImage(): self
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function setElements(array $elements): self
    {
        $this->elements = $elements;
        return $this;
    }

    public function addElement(KpyFaqElement $element): self
    {
        $this->elements[] = $element;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image' => $this->image,
            'elements' => array_map(static fn (KpyFaqElement $element) => $element->toArray(), $this->elements),
        ];
    }
}