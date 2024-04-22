<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\TransactionRepository;

class TransactionController extends AbstractController
{
    #[Route('/api/v1/transactions', name: 'get_transactions', methods: ['GET'])]
    public function getTransactions(TransactionRepository $transactionRepository): JsonResponse
    {
        $user = $this->security->getUser();
        $transactions = $transactionRepository->findBy(['client' => $user]);

        $data = array_map(function ($transaction) {
            return [
                'id' => $transaction->getId(),
                'created_at' => $transaction->getCreatedAt()->format(\DateTime::ISO8601),
                'type' => $transaction->getType(),
                'course_code' => $transaction->getCourse() ? $transaction->getCourse()->getCode() : null,
                'amount' => $transaction->getAmount(),
            ];
        }, $transactions);

        return $this->json($data);
    }
}
