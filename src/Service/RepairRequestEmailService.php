<?php

namespace App\Service;

use App\Entity\RepairRequest;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;
use Twig\Environment;

class RepairRequestEmailService
{
    private TransactionalEmailsApi $brevoApi;

    public function __construct(
        private Environment $twig,
        string $brevoApiKey
    ) {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $brevoApiKey);
        $this->brevoApi = new TransactionalEmailsApi(new Client(), $config);
    }

    public function sendClientConfirmation(RepairRequest $repairRequest): void
    {
        $htmlContent = $this->twig->render('emails/repair_request_client.html.twig', [
            'repairRequest' => $repairRequest,
        ]);

        $sendSmtpEmail = new SendSmtpEmail([
            'sender' => ['email' => 'rudy.saksik@gmail.com', 'name' => 'Recyclum'],
            'to' => [['email' => $repairRequest->getEmail(), 'name' => $repairRequest->getFullName()]],
            'subject' => 'Demande de réparation reçue - Recyclum',
            'htmlContent' => $htmlContent,
        ]);

        $this->brevoApi->sendTransacEmail($sendSmtpEmail);
    }

    public function sendAdminNotification(RepairRequest $repairRequest): void
    {
        $htmlContent = $this->twig->render('emails/repair_request_admin.html.twig', [
            'repairRequest' => $repairRequest,
        ]);

        $sendSmtpEmail = new SendSmtpEmail([
            'sender' => ['email' => 'rudy.saksik@gmail.com', 'name' => 'Recyclum'],
            'to' => [['email' => 'rudy.saksik@gmail.com', 'name' => 'Admin Recyclum']],
            'subject' => 'Nouvelle demande de réparation',
            'htmlContent' => $htmlContent,
        ]);

        $this->brevoApi->sendTransacEmail($sendSmtpEmail);
    }
}
