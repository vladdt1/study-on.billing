<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);

        $this->loadCourses($manager);
    }

    private function loadUsers(ObjectManager $manager): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $user->setEmail("user@gmail.com");
        $user->setPassword($this->hasher->hashPassword($user, 'password'));
        $user->setBalance(0.0);
        $manager->persist($user);

        $manager->flush();

        $admin = new User();
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $admin->setEmail("admin@gmail.com");
        $admin->setPassword($this->hasher->hashPassword($admin, 'password'));
        $admin->setBalance(500.0);
        $manager->persist($admin);

        $manager->flush();
    }

    private function loadCourses(ObjectManager $manager): void
{
    $course1 = new Course();
    $course1->setCode("code1");
    $course1->setTitle("Веб разработка");
    $course1->setDescription("Данный курс создан для начинающих веб разработчиков.");
    $course1->setType(Course::TYPE_BUY);
    $course1->setPrice(39.90);
    $manager->persist($course1);

    $course2 = new Course();
    $course2->setCode("code2");
    $course2->setTitle("Java для новичка");
    $course2->setDescription("Курс предоставляет все необходимые данные для чайников.");
    $course2->setType(Course::TYPE_RENT);
    $course2->setPrice(79.90);
    $manager->persist($course2);

    $course3 = new Course();
    $course3->setCode("code3");
    $course3->setTitle("Python");
    $course3->setDescription("Курс по основам Python.");
    $course3->setType(Course::TYPE_FREE);
    $course3->setPrice(0);
    $manager->persist($course3);

    $manager->flush();
}

}