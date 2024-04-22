<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use App\Entity\Transaction;
use App\Entity\Course;
use App\Entity\User;

class CourseController extends AbstractController
{
    private $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    #[Route('/api/v1/courses', name: 'get_courses', methods: ['GET'])]
    public function getCourses(CourseRepository $courseRepository): JsonResponse
    {
        $courses = $courseRepository->findAll();
        $data = [];
        $type = '';
        foreach ($courses as $course) {
            if($course->getType()==0){
                $type = 'free';
            }elseif($course->getType()==1){
                $type = 'rent';
            }elseif($course->getType()==2){
                $type = 'buy';
            }
            $data[] = [
                'code' => $course->getCode(),
                'type' => $type,
                'price' => $course->getPrice(),
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/api/v1/courses/{code}', name: 'get_course', methods: ['GET'])]
    public function getCourse(CourseRepository $courseRepository, string $code): JsonResponse
    {
        $course = $courseRepository->findOneBy(['code' => $code]);
        
        if (!$course) {
            return $this->json(['message' => 'Курса не существует'], 404);
        }

        $type = '';
        if($course->getType()==0){
            $type = 'free';
        }elseif($course->getType()==1){
            $type = 'rent';
        }elseif($course->getType()==0){
            $type = 'buy';
        }
        
        return $this->json([
            'code' => $course->getCode(),
            'type' => $type,
            'price' => $course->getPrice(),
        ]);
    }

    #[Route('/api/v1/courses/{code}/pay', name: 'pay_course', methods: ['POST'])]
    public function payCourse(Request $request, EntityManagerInterface $em, CourseRepository $courseRepository, string $code, #[CurrentUser] ?User $user, Course $course): JsonResponse
    {
        // Проверяем наличие токена авторизации
        if (!$user) {
            return $this->json(['message' => 'Пользователь не найден('], 404);
        }
    
        $course = $courseRepository->findOneByCode($code);

        try {
            $this->paymentService->payCourse($user, $course->getPrice(), $course->getCode());

            // Успешный ответ
            return $this->json([
                'success' => true,
                'course_type' => $course->getType(),
                'expires_at' => $transaction->getExpiresAt()->format(\DateTime::ISO8601),
            ]);
        } catch (\Exception $e) {
            // Ошибка
            return $this->json([
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }
}
