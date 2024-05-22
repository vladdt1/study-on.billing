<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PaymentService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function deposit(User $user, float $amount): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $transaction = (new Transaction())
                ->setClient($user)
                ->setType('deposit')
                ->setAmount($amount)
                ->setCreatedAt(new \DateTimeImmutable());

            $user->setBalance($user->getBalance() + $amount);

            $this->entityManager->persist($transaction);
            $this->entityManager->flush();

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->error("Ошибка пополнения: " . $e->getMessage());
            throw $e;
        }
    }

    public function payForCourse(User $user, Course $course, float $price): bool
    {
        if ($user->getBalance() < $price) {
            $this->logger->warning("Недостаточно средств для покупки курса {$course->getId()}");
            return false;
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $transaction = new Transaction();
            $transaction->setClient($user)
                ->setCourse($course)
                ->setAmount($price)
                ->setCreatedAt(new \DateTimeImmutable());

            if ($course->getType() === 1) {
                $transaction->setType('payment')
                    ->setExpiresAt((new \DateTimeImmutable())->modify('+100 month'));
            } elseif ($course->getType() === 2) {
                $transaction->setType('rent')
                    ->setExpiresAt((new \DateTimeImmutable())->modify('+1 month'));
            }

            $user->setBalance($user->getBalance() - $price);

            $this->entityManager->persist($transaction);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $connection->commit();
            return true;
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->error("Ошибка оплаты курса: " . $e->getMessage());
            return false;
        }
    }
}
