<?php

namespace App\Repository;

use App\Entity\CalendarItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CalendarItem>
 */
class CalendarItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalendarItem::class);
    }

    /**
     * Returns all CalendarItems where today == date - reminderDays.
     * Eagerly loads the related User to avoid N+1 when sending emails.
     *
     * @return CalendarItem[]
     */
    public function findDueForReminderToday(): array
    {
        // Use DBAL to express PostgreSQL interval arithmetic that DQL cannot represent.
        $ids = $this->getEntityManager()->getConnection()->executeQuery(
            "SELECT id FROM calendar_item
             WHERE date - (reminder_days * INTERVAL '1 day') = CURRENT_DATE"
        )->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->join('c.user', 'u')
            ->addSelect('u')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
