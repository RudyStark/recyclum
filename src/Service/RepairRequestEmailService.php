<?php

namespace App\Service;

use App\Entity\RepairRequest;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class RepairRequestEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {}

    public function sendClientConfirmation(RepairRequest $repairRequest): void
    {
        $email = (new Email())
            ->from('noreply@recyclum.fr')
            ->to($repairRequest->getEmail())
            ->subject('Demande de réparation reçue - Recyclum')
            ->html($this->twig->render('emails/repair_request_client.html.twig', [
                'repairRequest' => $repairRequest,
            ]));

        $this->mailer->send($email);
    }

    public function sendAdminNotification(RepairRequest $repairRequest): void
    {
        $email = (new Email())
            ->from('noreply@recyclum.fr')
            // email admin
            ->to('recyclum@yopmail.com')
            ->subject('Nouvelle demande de réparation')
            ->html($this->twig->render('emails/repair_request_admin.html.twig', [
                'repairRequest' => $repairRequest,
            ]));

        $this->mailer->send($email);
    }
}
