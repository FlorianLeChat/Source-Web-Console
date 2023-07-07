<?php

//
// Entité pour les tâches planifiées.
//
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\TaskRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
	#[ORM\Id]
	#[ORM\Column]
	#[ORM\GeneratedValue]
	private ?int $id = null;

	#[ORM\ManyToOne(inversedBy: "tasks")]
	#[ORM\JoinColumn(nullable: false)]
	private ?Server $server = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	#[Assert\Type("\DateTimeInterface")]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	private ?\DateTimeInterface $date = null;

	#[ORM\Column(length: 10)]
	#[Assert\Choice([Server::ACTION_SHUTDOWN, Server::ACTION_RESTART, Server::ACTION_UPDATE, Server::ACTION_SERVICE])]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	private ?string $action = null;

	#[ORM\Column(length: 10)]
	#[Assert\Choice([Task::STATE_ERROR, Task::STATE_WAITING, Task::STATE_RUNNING, Task::STATE_FINISHED])]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	private ?string $state = null;

	public const STATE_ERROR = "error";
	public const STATE_WAITING = "waiting";
	public const STATE_RUNNING = "running";
	public const STATE_FINISHED = "finished";

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

	public function setAction(?string $action): self
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