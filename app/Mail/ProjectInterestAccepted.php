<?php

namespace App\Mail;

use App\Models\ProjectInterest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells a contractor that the homeowner accepted them, with the homeowner contact details.
 */
class ProjectInterestAccepted extends Mailable
{
    public function __construct(public ProjectInterest $interest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                Setting::get('notifications_from_email', config('mail.from.address')),
                config('mail.from.name'),
            ),
            subject: __('projects.mail.accepted_subject', ['project' => $this->interest->project->title]),
        );
    }

    public function content(): Content
    {
        $homeowner = $this->interest->project->homeowner;
        $domain = $this->interest->tenant->domains()->first()?->domain;

        return new Content(
            markdown: 'emails.project-interest-accepted',
            with: [
                'contractorName' => $this->interest->tenant->name,
                'projectTitle' => $this->interest->project->title,
                'homeownerName' => $homeowner->name,
                'homeownerEmail' => $homeowner->email,
                'homeownerPhone' => $homeowner->phone,
                'projectsUrl' => $domain
                    ? parse_url(config('app.url'), PHP_URL_SCHEME).'://'.$domain.'.'.parse_url(config('app.url'), PHP_URL_HOST).'/projects'
                    : null,
            ],
        );
    }
}
