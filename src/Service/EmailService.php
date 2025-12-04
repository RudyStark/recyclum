<?php

namespace App\Service;

use App\Entity\RepairRequest;
use App\Entity\RepairAppointment;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;
use Twig\Environment;

class EmailService
{
    private TransactionalEmailsApi $brevoApi;
    private string $senderEmail;
    private string $senderName;
    private string $adminEmail;

    public function __construct(
        private Environment $twig,
        string $brevoApiKey,
        string $senderEmail = 'rudy.saksik@gmail.com',
        string $senderName = 'Recyclum',
        string $adminEmail = 'rudy.saksik@gmail.com'
    ) {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $brevoApiKey);
        $this->brevoApi = new TransactionalEmailsApi(new Client(), $config);
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
        $this->adminEmail = $adminEmail;
    }

    /**
     * Méthode générique d'envoi d'email
     */
    private function sendEmail(string $to, string $toName, string $subject, string $htmlContent): void
    {
        $sendSmtpEmail = new SendSmtpEmail([
            'sender' => ['email' => $this->senderEmail, 'name' => $this->senderName],
            'to' => [['email' => $to, 'name' => $toName]],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ]);

        $this->brevoApi->sendTransacEmail($sendSmtpEmail);
    }

    // ========================================================================
    // EMAILS DEMANDES DE RÉPARATION (Soumission initiale)
    // ========================================================================

    /**
     * Confirmation au client après soumission de sa demande
     */
    public function sendRepairRequestClientConfirmation(RepairRequest $repairRequest): void
    {
        $htmlContent = $this->twig->render('emails/repair_request_client.html.twig', [
            'repairRequest' => $repairRequest,
        ]);

        $this->sendEmail(
            $repairRequest->getEmail(),
            $repairRequest->getFullName(),
            'Demande de réparation reçue - Recyclum',
            $htmlContent
        );
    }

    /**
     * Notification admin quand nouvelle demande soumise
     */
    public function sendRepairRequestAdminNotification(RepairRequest $repairRequest): void
    {
        $htmlContent = $this->twig->render('emails/repair_request_admin.html.twig', [
            'repairRequest' => $repairRequest,
        ]);

        $this->sendEmail(
            $this->adminEmail,
            'Admin Recyclum',
            'Nouvelle demande de réparation - #' . $repairRequest->getId(),
            $htmlContent
        );
    }

    // ========================================================================
    // EMAILS RÉPONSE ADMIN (Acceptation/Refus)
    // ========================================================================

    /**
     * Email d'acceptation avec lien de prise de RDV
     */
    public function sendRepairAcceptanceEmail(RepairRequest $repairRequest, string $customMessage): void
    {
        $htmlContent = $this->twig->render('emails/repair_accepted.html.twig', [
            'request' => $repairRequest,
            'customMessage' => $customMessage,
            'responseType' => 'accept'
        ]);

        $this->sendEmail(
            $repairRequest->getEmail(),
            $repairRequest->getFullName(),
            '✅ Votre demande de réparation a été acceptée - Recyclum',
            $htmlContent
        );
    }

    /**
     * Email de refus de la demande
     */
    public function sendRepairRejectionEmail(RepairRequest $repairRequest, string $customMessage): void
    {
        $htmlContent = $this->twig->render('emails/repair_rejected.html.twig', [
            'request' => $repairRequest,
            'customMessage' => $customMessage,
            'responseType' => 'reject'
        ]);

        $this->sendEmail(
            $repairRequest->getEmail(),
            $repairRequest->getFullName(),
            'Votre demande de réparation - Recyclum',
            $htmlContent
        );
    }

    // ========================================================================
    // EMAILS RENDEZ-VOUS (Confirmation RDV)
    // ========================================================================

    /**
     * Confirmation au client après validation du RDV
     */
    public function sendAppointmentConfirmationToClient(RepairRequest $repairRequest, RepairAppointment $appointment): void
    {
        $htmlContent = $this->twig->render('emails/appointment_confirmed.html.twig', [
            'request' => $repairRequest,
            'appointment' => $appointment
        ]);

        $this->sendEmail(
            $repairRequest->getEmail(),
            $repairRequest->getFullName(),
            '✅ Rendez-vous confirmé - Recyclum',
            $htmlContent
        );
    }

    /**
     * Notification admin quand nouveau RDV confirmé
     */
    public function sendAppointmentNotificationToAdmin(RepairRequest $repairRequest, RepairAppointment $appointment, string $viewUrl): void
    {
        $htmlContent = $this->twig->render('emails/admin_new_appointment.html.twig', [
            'request' => $repairRequest,
            'appointment' => $appointment,
            'viewUrl' => $viewUrl,
        ]);

        $this->sendEmail(
            $this->adminEmail,
            'Admin Recyclum',
            '🔔 Nouveau RDV confirmé - Demande #' . $repairRequest->getId(),
            $htmlContent
        );
    }

    // ========================================================================
    // EMAILS FUTURS (À implémenter plus tard)
    // ========================================================================

    /**
     * Rappel 24h avant le RDV
     */
    public function sendAppointmentReminder(RepairRequest $repairRequest, RepairAppointment $appointment): void
    {
        // TODO: Template à créer
        // emails/appointment_reminder.html.twig
    }

    /**
     * Email après réparation terminée
     */
    public function sendRepairCompletedEmail(RepairRequest $repairRequest): void
    {
        // TODO: Template à créer
        // emails/repair_completed.html.twig
    }

    /**
     * Email de facture
     */
    public function sendInvoiceEmail(RepairRequest $repairRequest, string $invoicePath): void
    {
        // TODO: Template à créer + pièce jointe
        // emails/invoice.html.twig
    }
}
