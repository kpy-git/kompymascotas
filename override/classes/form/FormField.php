<?php

class FormField extends FormFieldCore
{
    private array $classes = [];

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function setClasses(array $classes): FormField
    {
        $this->classes = $classes;
        return $this;
    }

    public function addClass(string $class): FormField
    {
        $this->classes[] = $class;
        $this->classes = array_unique($this->classes);

        return $this;
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        $data['classes'] = implode(' ', $this->classes);

        return $data;
    }
}