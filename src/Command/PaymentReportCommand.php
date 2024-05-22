<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment as TwigEnvironment;
use App\Entity\Course;
use App\Entity\Transaction;

class PaymentReportCommand extends Command
{
    protected static $defaultName = 'payment:report';

    private $mailer;
    private $entityManager;
    private $twig;

    public function __construct(MailerInterface $mailer, EntityManagerInterface $entityManager, TwigEnvironment $twig)
    {
        parent::__construct();
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->twig = $twig;
    }

    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Generates and sends a payment report for the last month.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateFrom = (new \DateTime())->modify('-1 month');

        $transactionRepo = $this->entityManager->getRepository(Transaction::class);
        $transactions = $transactionRepo->createQueryBuilder('t')
            ->where('t.createdAt >= :dateFrom')
            ->setParameter('dateFrom', $dateFrom)
            ->getQuery()
            ->getResult();

        $reportDetails = [];
        $totalSum = 0;

        foreach ($transactions as $transaction) {
            $course = $transaction->getCourse();
            if (!$course) {
                continue;
            }

            $courseTitle = $course->getTitle();
            $courseCategory = $course->getType() == Course::TYPE_RENT ? 'Rental' : 'Purchase';
            $amount = $transaction->getAmount();

            if (!isset($reportDetails[$courseTitle])) {
                $reportDetails[$courseTitle] = [
                    'type' => $courseCategory,
                    'count' => 0,
                    'total' => 0,
                ];
            }

            $reportDetails[$courseTitle]['count']++;
            $reportDetails[$courseTitle]['total'] += $amount;
            $totalSum += $amount;
        }

        $reportContent = $this->twig->render('email/email.html.twig', [
            'reportDetails' => $reportDetails,
            'totalSum' => $totalSum,
            'oneDate' => $dateFrom->format('d.m.Y'),
            'twoDate' => (new \DateTime())->format('d.m.Y'),
        ]);

        $email = (new Email())
            ->from('no-reply@example.com')
            ->to('admin@example.com')
            ->subject('Monthly Payment Report')
            ->html($reportContent);

        $this->mailer->send($email);

        $output->writeln('Payment report has been sent successfully.');

        return Command::SUCCESS;
    }
}
