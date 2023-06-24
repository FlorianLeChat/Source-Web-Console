<?php

//
// Entité pour les tâches planifiées.
//
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\TaskRepository;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\ManyToOne(inversedBy: "date")]
	#[ORM\JoinColumn(nullable: false)]
	private ?Server $server = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeInterface $date = null;

	#[ORM\Column(length: 10)]
	private ?string $action = null;

	#[ORM\Column(length: 10)]
	private ?string $state = null;

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

	public function getState(): ?string
	{
		return $this->state;
	}

	public function setState(string $state): self
	{
		$this->state = $state;

		return $this;
	}
}