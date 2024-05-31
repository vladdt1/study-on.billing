<?php

namespace App\Controller;

use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CourseRepository;
use App\Entity\User;

class PaymentController extends AbstractController
{
    private PaymentService $paymentService;
    private EntityManagerInterface $entityManager;
    private CourseRepository $courseRepository;

    public function __construct(
        PaymentService $paymentService,
        EntityManagerInterface $entityManager,
        CourseRepository $courseRepository
    ) {
        $this->paymentService = $paymentService;
        $this->entityManager = $entityManager;
        $this->courseRepository = $courseRepository;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/courses/{code}/pay",
     *     summary="Оплата курса",
     *     description="Оплата курса для пользователя.",
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         required=true,
     *         description="Код курса",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         description="",
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Оплата успешно выполнена",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="course_type", type="integer", example=1),
     *             @OA\Property(property="expires_at", type="string", example="2021-12-31T23:59:59+00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Требуется аутентификация"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Курса не существует"
     *     ),
     *     @OA\Response(
     *         response=402,
     *         description="Недостаточно средств"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка при оплате"
     *     )
     * )
     */
    #[Route('/api/v1/courses/{code}/pay', name: 'pay_course', methods: ['POST'])]
    public function payForCourse(string $code, #[CurrentUser] ?User $currentUser): JsonResponse
    {
        if (!$currentUser) {
            return $this->json(['message' => 'Требуется аутентификация'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $course = $this->courseRepository->findOneBy(['code' => $code]);
        if (!$course) {
            return $this->json(['message' => 'Курса не существует'], JsonResponse::HTTP_NOT_FOUND);
        }

        if ($currentUser->getBalance() < $course->getPrice()) {
            return $this->json(['message' => 'Недостаточно средств'], JsonResponse::HTTP_PAYMENT_REQUIRED);
        }

        $paymentSuccess = $this->paymentService->payForCourse($currentUser, $course, $course->getPrice());
        if (!$paymentSuccess) {
            return $this->json(['message' => 'Ошибка при оплате'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $expiresAt = $course->getType() === Course::TYPE_RENT
            ? (new \DateTimeImmutable())->modify('+1 month')->format(\DateTime::ISO8601)
            : (new \DateTimeImmutable())->format(\DateTime::ISO8601);

        return $this->json([
            'success' => true,
            'course_type' => $course->getType(),
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/deposit",
     *     summary="Пополнение счета",
     *     description="Позволяет пользователю пополнить свой баланс.",
     *     @OA\RequestBody(
     *         description="Сумма пополнения",
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", example=100.0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Баланс успешно пополнен",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="balance", type="number", example=150.0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Некорректная сумма пополнения"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Требуется аутентификация"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка при пополнении"
     *     )
     * )
     */
    #[Route('/api/v1/deposit', name: 'deposit', methods: ['POST'])]
    public function depositFunds(Request $request, #[CurrentUser] ?User $currentUser): JsonResponse
    {
        if (!$currentUser) {
            return $this->json(['message' => 'Требуется аутентификация'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $requestData = json_decode($request->getContent(), true);
        
        $amount = $requestData['amount'] ?? null;

        if (!$amount || $amount <= 0) {
            return $this->json(['message' => 'Некорректная сумма'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $this->paymentService->deposit($currentUser, (float)$amount);
            return $this->json(['success' => true, 'balance' => $currentUser->getBalance()]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Ошибка при пополнении: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
