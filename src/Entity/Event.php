<?php

//
// Entité pour les événements journalisés.
//
namespace App\Entity;

use ApiPlatform\Metadata\Get;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\EventRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ApiResource(
	security: "is_granted(\"ROLE_ADMIN\")",
	operations: [
		new Get(normalizationContext: ["groups" => "event"]),
		new GetCollection(normalizationContext: ["groups" => "events"])
	]
)]
class Event
{
	#[ORM\Id]
	#[ORM\Column]
	#[ORM\GeneratedValue]
	#[Groups(["events", "event"])]
	private ?int $id = null;

	#[ORM\ManyToOne(inversedBy: "events")]
	#[ORM\JoinColumn(nullable: false)]
	#[Groups(["events", "event"])]
	private ?Server $server = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	#[Groups(["events", "event"])]
	private ?\DateTimeInterface $date = null;

	#[ORM\Column(length: 10)]
	#[Groups(["events", "event"])]
	private ?string $action = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getServer(): ?Server
	{
		return $this->server;
	}

	public function setServer(?Server $server): self
	{
		$this->server = $server;

		return $this;
	}

	public function getDate(): ?\DateTimeInterface
	{
		return $this->date;
	}

	public function setDate(\DateTimeInterface $date): self
	{
		$this->date = $date;

		return $this;
	}

	public function getAction(): ?string
	{
		return $this->action;
	}

	public function setAction(string $action): self
	{
		$this->action = $action;

		return $this;
	}
}
