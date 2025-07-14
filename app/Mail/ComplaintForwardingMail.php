<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ComplaintForwardingMail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailData;

    /**
     * Create a new message instance.
     */
    public function __construct($emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->emailData['subject'];
        
        // Add priority indicator to subject if high priority
        if (isset($this->emailData['priority']) && $this->emailData['priority'] === 'Tinggi') {
            $subject = '[PRIORITAS TINGGI] ' . $subject;
        }

        // Add test indicator if this is a test email
        if (isset($this->emailData['is_test']) && $this->emailData['is_test']) {
            $subject = '[TEST] ' . $subject;
        }

        return $this->subject($subject)
                    ->view('emails.complaint-forwarding')
                    ->with([
                        'emailData' => $this->emailData,
                        'to' => $this->emailData['to'],
                        'messageContent' => $this->emailData['message'],
                        'complaintId' => $this->emailData['complaint_id'] ?? null,
                        'departmentName' => $this->emailData['department_name'] ?? 'Unknown',
                        'priority' => $this->emailData['priority'] ?? 'Normal',
                        'sentAt' => $this->emailData['sent_at'] ?? now()->format('d M Y H:i:s'),
                        'isTest' => $this->emailData['is_test'] ?? false
                    ]);
    }
}