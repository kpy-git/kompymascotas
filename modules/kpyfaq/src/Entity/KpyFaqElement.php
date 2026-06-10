<?php

namespace PrestaShop\Module\KpyFaq\Entity;

class KpyFaqElement
{
    private int $id;

    private int $position;

    private string $question;

    private string $answer;

    private string $link_rewrite;

    private int $sectionId;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;
        return $this;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): self
    {
        $this->answer = $answer;
        return $this;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function getSectionId(): int
    {
        return $this->sectionId;
    }

    public function setSectionId(int $sectionId): KpyFaqElement
    {
        $this->sectionId = $sectionId;
        return $this;
    }

    public function getLinkRewrite(): string
    {
        return $this->link_rewrite;
    }

    public function setLinkRewrite(string $link_rewrite): KpyFaqElement
    {
        $this->link_rewrite = $link_rewrite;
        return $this;
    }

}