<?php

namespace App\Entity;

use App\Repository\ExtractedArticleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExtractedArticleRepository::class)]
class TrustedSites
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $domain;

    #[ORM\Column(type: 'integer')]
    private int $realHits;

    #[ORM\Column(type: 'integer')]
    private int $fakeHits;

    #[ORM\Column(type: 'integer')]
    private int $totalHits;

    #[ORM\Column(type: 'float', nullable: true)]
    private float $percentage;

    public function __construct(string $domain)
    {
        $this->domain = $domain;
        $this->realHits = 0;
        $this->fakeHits = 0;
        $this->totalHits = 0;
        $this->percentage = 0.0;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId($id): TrustedSites
    {
        $this->id = $id;

        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain($domain): TrustedSites
    {
        $this->domain = $domain;

        return $this;

    }

    public function getRealHits(): int
    {
        return $this->realHits;
    }

    public function setRealHits($realHits): TrustedSites
    {
        $this->realHits = $realHits;

        return $this;
    }

    public function getFakeHits(): int
    {
        return $this->fakeHits;
    }

    public function setFakeHits($fakeHits): TrustedSites
    {
        $this->fakeHits = $fakeHits;

        return $this;
    }

    public function getTotalHits(): int
    {
        return $this->totalHits;
    }

    public function setTotalHits($totalHits): TrustedSites
    {
        $this->totalHits = $totalHits;

        return $this;
    }

    public function getPercentage(): float
    {
        return $this->percentage;
    }

    public function setPercentage(float $percentage): TrustedSites
    {
        $this->percentage = $percentage;

        return $this;
    }



}
