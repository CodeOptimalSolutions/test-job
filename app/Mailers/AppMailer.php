<?php

namespace DTApi\Mailers;

use DTApi\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Mail\Mailer;

class AppMailer implements MailerInterface
{
    /**
     * The Laravel Mailer instance.
     *
     * @var Mailer
     */
    protected $mailer;
    /**
     * The sender of the email.
     *
     * @var string
     */

    protected $from = 'admin@digitaltolk.se';

    protected $from_name = 'DigitalTolk';

    /**
     * The recipient of the email.
     *
     * @var string
     */
    protected $to;
    protected $to_name;
    /**
     * The view for the email.
     *
     * @var string
     */
    protected $view;
    /**
     * The data associated with the view for the email.
     *
     * @var array
     */
    protected $data = [];
    protected $subject;
    /**
     * Create a new app mailer instance.
     *
     * @param Mailer $mailer
     */
    public function __construct()
    {
    }
    /**
     * Deliver the email confirmation.
     *
     * @param  \App\Models\User $user
     * @return void
     */
    public function sendEmailConfirmationTo(User $user)
    {
        $this->to = $user->email;
        $this->view = 'emails.confirm';
        $this->data = compact('user');
        $this->deliver();
    }

    public function send($email, $name, $subject, $view, $data) {

        $this->to = $email;
        $this->to_name = $name;
        $this->subject = $subject;
        $this->view = $view;
        $this->data = $data;
        $this->deliver();

    }
    /**
     * Deliver the email.
     *
     * @return void
     */
    public function deliver()
    {
        Mail::send($this->view, $this->data, function ($message) {
            $message->from($this->from, $this->from_name)
                ->to($this->to, $this->to_name)->subject($this->subject);
        });
    }
}