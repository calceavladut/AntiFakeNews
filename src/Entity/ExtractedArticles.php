<?php

namespace App\Entity;

class ExtractedArticles
{
//    private int $id;

    private string $url;

    private string $title;

    private string $text;

    private string $fake;

    private string $real;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getFake(): string
    {
        return $this->fake;
    }

    /**
     * @param string $fake
     */
    public function setFake(string $fake): void
    {
        $this->fake = $fake;
    }

    /**
     * @return string
     */
    public function getReal(): string
    {
        return $this->real;
    }

    /**
     * @param string $real
     */
    public function setReal(string $real): void
    {
        $this->real = $real;
    }


}
