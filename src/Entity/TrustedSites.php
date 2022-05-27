<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

class TrustedSites
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $domain;

    #[ORM\Column(type: 'integer')]
    private int $trueHits;

    #[ORM\Column(type: 'integer')]
    private int $falseHits;

    #[ORM\Column(type: 'integer')]
    private int $totalHits;

    public function __construct(string $domain)
    {
        $this->domain = $domain;
        $this->trueHits = 0;
        $this->falseHits = 0;
        $this->totalHits = 0;
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

    public function getTrueHits(): int
    {
        return $this->trueHits;
    }

    public function setTrueHits($trueHits): TrustedSites
    {
        $this->trueHits = $trueHits;

        return $this;
    }

    public function getFalseHits(): int
    {
        return $this->falseHits;
    }

    public function setFalseHits($falseHits): TrustedSites
    {
        $this->falseHits = $falseHits;

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

}
