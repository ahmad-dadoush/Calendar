<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\CalendarItemRepository::class)]
class CalendarItem
{
    public const REMINDER_ONE_DAY = 1;
    public const REMINDER_TWO_DAYS = 2;
    public const REMINDER_FOUR_DAYS = 4;
    public const REMINDER_ONE_WEEK = 7;
    public const REMINDER_TWO_WEEKS = 14;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: 'Bitte waehle ein Datum aus.')]
    private ?DateTimeInterface $date = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Bitte gib eine Bezeichnung ein.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Die Bezeichnung darf hoechstens {{ limit }} Zeichen lang sein.'
    )]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Bitte waehle eine Erinnerung aus.')]
    #[Assert\Choice(
        choices: [
            self::REMINDER_ONE_DAY,
            self::REMINDER_TWO_DAYS,
            self::REMINDER_FOUR_DAYS,
            self::REMINDER_ONE_WEEK,
            self::REMINDER_TWO_WEEKS,
        ],
        message: 'Bitte waehle eine gueltige Erinnerung aus.'
    )]
    private ?int $reminderDays = null;

    #[ORM\ManyToOne(inversedBy: 'calendarItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = null === $description ? null : trim($description);

        return $this;
    }

    public function getReminderDays(): ?int
    {
        return $this->reminderDays;
    }

    public function setReminderDays(?int $reminderDays): static
    {
        $this->reminderDays = $reminderDays;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return array<string, int>
     */
    public static function getReminderChoices(): array
    {
        return [
            '1 Tag' => self::REMINDER_ONE_DAY,
            '2 Tage' => self::REMINDER_TWO_DAYS,
            '4 Tage' => self::REMINDER_FOUR_DAYS,
            '1 Woche' => self::REMINDER_ONE_WEEK,
            '2 Wochen' => self::REMINDER_TWO_WEEKS,
        ];
    }

    public function getFormattedDate(): ?string
    {
        if (null === $this->date) {
            return null;
        }

        return $this->date->format('d.m.');
    }
}