<?php

//
// Entité pour les statistiques des serveurs distants.
//
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\StatsRepository;

#[ORM\Entity(repositoryClass: StatsRepository::class)]
class Stats
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\ManyToOne(inversedBy: "stats")]
	#[ORM\JoinColumn(nullable: false)]
	private ?server $server = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeInterface $date = null;

	#[ORM\Column(type: Types::SMALLINT)]
	private ?int $playerCount = null;

	#[ORM\Column(type: Types::SMALLINT)]
	private ?int $cpuUsage = null;

	#[ORM\Column(type: Types::SMALLINT)]
	private ?int $tickRate = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getServer(): ?server
	{
		return $this->server;
	}

	public function setServer(?server $server): static
	{
		$this->server = $server;

		return $this;
	}

	public function getDate(): ?\DateTimeInterface
	{
		return $this->date;
	}

	public function setDate(\DateTimeInterface $date): static
	{
		$this->date = $date;

		return $this;
	}

	public function getPlayerCount(): ?int
	{
		return $this->playerCount;
	}

	public function setPlayerCount(int $playerCount): static
	{
		$this->playerCount = $playerCount;

		return $this;
	}

	public function getCpuUsage(): ?int
	{
		return $this->cpuUsage;
	}

	public function setCpuUsage(int $cpuUsage): static
	{
		$this->cpuUsage = $cpuUsage;

		return $this;
	}

	public function getTickRate(): ?int
	{
		return $this->tickRate;
	}

	public function setTickRate(int $tickRate): static
	{
		$this->tickRate = $tickRate;

		return $this;
	}
}