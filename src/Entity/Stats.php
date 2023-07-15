<?php

//
// Entité pour les statistiques des serveurs distants.
//
namespace App\Entity;

use ApiPlatform\Metadata\Get;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\StatsRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StatsRepository::class)]
#[ApiResource(
	security: "is_granted(\"ROLE_ADMIN\")",
	operations: [
		new Get(normalizationContext: ["groups" => "stat"]),
		new GetCollection(normalizationContext: ["groups" => "stats"])
	]
)]
class Stats
{
	#[ORM\Id]
	#[ORM\Column]
	#[ORM\GeneratedValue]
	#[Groups(["stat", "stats"])]
	private ?int $id = null;

	#[ORM\ManyToOne(inversedBy: "stats")]
	#[ORM\JoinColumn(nullable: false)]
	#[Groups(["stat", "stats"])]
	private ?Server $server = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	#[Groups(["stat", "stats"])]
	private ?\DateTimeInterface $date = null;

	#[ORM\Column(type: Types::SMALLINT)]
	#[Groups(["stat", "stats"])]
	private ?int $playerCount = null;

	#[ORM\Column(type: Types::FLOAT)]
	#[Groups(["stat", "stats"])]
	private ?int $cpuUsage = null;

	#[ORM\Column(type: Types::FLOAT)]
	#[Groups(["stat", "stats"])]
	private ?int $tickRate = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getServer(): ?Server
	{
		return $this->server;
	}

	public function setServer(?Server $server): static
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

	public function getCpuUsage(): ?float
	{
		return $this->cpuUsage;
	}

	public function setCpuUsage(float $cpuUsage): static
	{
		$this->cpuUsage = $cpuUsage;

		return $this;
	}

	public function getTickRate(): ?float
	{
		return $this->tickRate;
	}

	public function setTickRate(float $tickRate): static
	{
		$this->tickRate = $tickRate;

		return $this;
	}
}