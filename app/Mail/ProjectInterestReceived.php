<?php

namespace App\Mail;

use App\Models\ProjectInterest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells a homeowner that a contractor is interested in their project.
 *
 * Sent right away instead of queued: it is triggered from a tenant panel, where
 * the default database connection (and so the database queue) is the tenant's.
 */
class ProjectInterestReceived extends Mailable
{
    public function __construct(public ProjectInterest $interest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                Setting::get('notifications_from_email', config('mail.from.address')),
                config('mail.from.name'),
            ),
            subject: __('projects.mail.received_subject', ['contractor' => $this->interest->tenant->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.project-interest-received',
            with: [
                'homeownerName' => $this->interest->project->homeowner->name,
                'contractorName' => $this->interest->tenant->name,
                'projectTitle' => $this->interest->project->title,
                'interestMessage' => $this->interest->message,
                'projectUrl' => route('homeowner.projects.show', $this->interest->project),
            ],
        );
    }
}
