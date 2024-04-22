<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Course;

class PaymentService
{
    private $entityManager;
    private $transactionRepository;

    public function __construct(EntityManagerInterface $entityManager, TransactionRepository $transactionRepository)
    {
        $this->entityManager = $entityManager;
        $this->transactionRepository = $transactionRepository;
    }

    public function deposit(User $user, float $amount): void
    {
        $this->entityManager->beginTransaction();

        try {
            $transaction = new Transaction();
            $transaction->setClient($user);
            $transaction->setType(Transaction::TYPE_DEPOSIT);
            $transaction->setAmount($amount);
            $this->entityManager->persist($transaction);

            $user->setBalance($user->getBalance() + $amount);
            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function payCourse(User $user, float $amount, string $courseCode): void
    {
        $this->entityManager->beginTransaction();

        try {
            // Проверяем, достаточно ли у пользователя средств для оплаты
            if ($user->getBalance() < $amount) {
                throw new \Exception('На вашем счету недостаточно средств', 406);
            }

            // Находим курс по его коду
            $course = $this->entityManager->getRepository(Course::class)->findOneBy(['code' => $courseCode]);
            if (!$course) {
                throw new \Exception('Курс не найден', 404);
            }

            $transaction = new Transaction();
            $transaction->setClient($user);
            $transaction->setCourse($course);
            $transaction->setType(Transaction::TYPE_PAYMENT);
            $transaction->setAmount($amount);
            $this->entityManager->persist($transaction);

            // Списываем средства со счета пользователя
            $user->setBalance($user->getBalance() - $amount);
            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
}
