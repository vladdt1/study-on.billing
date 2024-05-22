<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

class TransactionController extends AbstractController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/transactions",
     *     summary="Список транзакций",
     *     description="Список всех транзакций пользователя.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Список транзакций",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", example="2023-05-15T12:34:56+00:00"),
     *                 @OA\Property(property="type", type="string", example="purchase"),
     *                 @OA\Property(property="course_code", type="string", example="CS101"),
     *                 @OA\Property(property="amount", type="float", example=100.50),
     *                 @OA\Property(property="expires_at", type="string", example="2023-06-15T12:34:56+00:00", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Требуется аутентификация"
     *     )
     * )
     */
    #[Route('/api/v1/transactions', name: 'get_transactions', methods: ['GET'])]
    public function fetchUserTransactions(TransactionRepository $transactionRepository): JsonResponse
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            $errors = ['error' => 'Требуется авторизация'];
            return new JsonResponse($errors, Response::HTTP_UNAUTHORIZED);
        }
        $userTransactions = $transactionRepository->findBy(['client' => $currentUser]);

        $transactionList = array_map(static function ($transaction) {
            return [
                'id' => $transaction->getId(),
                'created_at' => $transaction->getCreatedAt()->format(\DateTime::ISO8601),
                'type' => $transaction->getType(),
                'course_id' => $transaction->getCourse() ? $transaction->getCourse()->getCode() : null,
                'amount' => $transaction->getAmount(),
                'expires_at' => $transaction->getExpiresAt() ? $transaction->getExpiresAt()->format(\DateTime::ISO8601) : null,
            ];
        }, $userTransactions);

        return $this->json($transactionList);
    }
}
