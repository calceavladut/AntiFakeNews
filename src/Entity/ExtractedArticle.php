<?php

namespace App\Entity;

use App\Repository\ExtractedArticleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExtractedArticleRepository::class)]
class ExtractedArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $url;

    #[ORM\Column(type: 'string', length: 255)]
    private $text;

    #[ORM\Column(type: 'text')]
    private $original_title;

    #[ORM\Column(type: 'text')]
    private $original_content;

    #[ORM\Column(type: 'text', nullable: true)]
    private $translated_title;

    #[ORM\Column(type: 'text', nullable: true)]
    private $translated_content;

    #[ORM\Column(type: 'float', nullable: true)]
    private $real_score;

    #[ORM\Column(type: 'float', nullable: true)]
    private $fake_score;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param mixed $text
     * @return ExtractedArticle
     */
    public function setText($text): ExtractedArticle
    {
        $this->text = $text;

        return $this;
    }

    public function getOriginalTitle(): ?string
    {
        return $this->original_title;
    }

    public function setOriginalTitle(string $original_title): self
    {
        $this->original_title = $original_title;

        return $this;
    }

    public function getOriginalContent(): ?string
    {
        return $this->original_content;
    }

    public function setOriginalContent(string $original_content): self
    {
        $this->original_content = $original_content;

        return $this;
    }

    public function getTranslatedTitle(): ?string
    {
        return $this->translated_title;
    }

    public function setTranslatedTitle(?string $translated_title): self
    {
        $this->translated_title = $translated_title;

        return $this;
    }

    public function getTranslatedContent(): ?string
    {
        return $this->translated_content;
    }

    public function setTranslatedContent(?string $translated_content): self
    {
        $this->translated_content = $translated_content;

        return $this;
    }

    public function getRealScore(): ?float
    {
        return $this->real_score;
    }

    public function setRealScore(?float $real_score): self
    {
        $this->real_score = $real_score;

        return $this;
    }

    public function getFakeScore(): ?float
    {
        return $this->fake_score;
    }

    public function setFakeScore(?float $fake_score): self
    {
        $this->fake_score = $fake_score;

        return $this;
    }
}
