<?php

namespace App\Entity;

//
// Entité pour les messages de contact.
//
use ApiPlatform\Metadata\Get;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ContactRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ApiResource(
	security: "is_granted(\"ROLE_ADMIN\")",
	operations: [
		new Get(),
		new GetCollection()
	]
)]
class Contact
{
	#[ORM\Id]
	#[ORM\Column]
	#[ORM\GeneratedValue]
	private ?int $id = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeInterface $date = null;

	#[ORM\Column(length: 100)]
	#[Assert\Email]
	#[Assert\Length(min: 10, max: 100)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	#[Assert\NoSuspiciousCharacters]
	private ?string $email = null;

	#[ORM\Column(length: 255)]
	#[Assert\Length(min: 10, max: 255)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	private ?string $subject = null;

	#[ORM\Column(length: 5000)]
	#[Assert\Length(min: 50, max: 5000)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	#[Assert\NoSuspiciousCharacters]
	private ?string $content = null;

	public function getId(): ?int
	{
		return $this->id;
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

	public function getEmail(): ?string
	{
		return $this->email;
	}

	public function setEmail(?string $email): self
	{
		$this->email = $email;

		return $this;
	}

	public function getSubject(): ?string
	{
		return $this->subject;
	}

	public function setSubject(?string $subject): self
	{
		$this->subject = $subject;

		return $this;
	}

	public function getContent(): ?string
	{
		return $this->content;
	}

	public function setContent(?string $content): self
	{
		$this->content = $content;

		return $this;
	}
}