<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use App\Entity\Course;

class PaymentEndingNotificationCommand extends Command
{
    protected static $defaultName = 'payment:ending:notification';

    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepo;

    public function __construct(MailerInterface $mailer, EntityManagerInterface $entityManager, UserRepository $userRepo)
    {
        parent::__construct();
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->userRepo = $userRepo;
    }

    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Sends notification emails to users whose course rentals are ending soon.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userList = $this->userRepo->findUsersWithEndingRentals();

        foreach ($userList as $user) {
            $endingRentals = $user->getTransactions()->filter(function($transaction) {
                $expiresAt = $transaction->getExpiresAt();
                $tomorrow = (new \DateTime())->modify('+1 day');
                return $transaction->getCourse()->getType() === Course::TYPE_RENT &&
                       $expiresAt >= $tomorrow->setTime(0, 0, 0) &&
                       $expiresAt < $tomorrow->modify('+1 day')->setTime(0, 0, 0);
            });

            if ($endingRentals->isEmpty()) {
                continue;
            }

            $courseDetails = array_map(function($transaction) {
                return sprintf(
                    "%s действует до %s",
                    $transaction->getCourse()->getTitle(),
                    $transaction->getExpiresAt()->format('d.m.Y H:i')
                );
            }, $endingRentals->toArray());

            $courseListStr = implode("\n", $courseDetails);

            $email = (new Email())
                ->from('no-reply@example.com')
                ->to($user->getEmail())
                ->subject('Course Rental Expiry Notification')
                ->text(sprintf(
                    "Dear Customer, the following courses you rented are expiring soon:\n%s",
                    $courseListStr
                ));

            $this->mailer->send($email);
        }

        $output->writeln('Notification emails have been sent successfully.');

        return Command::SUCCESS;
    }
}
