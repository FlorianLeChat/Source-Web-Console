<?php

namespace App\Entity;

//
// Entité pour les messages de contact.
//
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\ContactRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeInterface $timestamp = null;

	#[ORM\Column(length: 100)]
	#[Assert\Email]
	#[Assert\Length(min: 10, max: 100)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
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
	private ?string $content = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getTimestamp(): ?\DateTimeInterface
	{
		return $this->timestamp;
	}

	public function setTimestamp(\DateTimeInterface $timestamp): self
	{
		$this->timestamp = $timestamp;

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