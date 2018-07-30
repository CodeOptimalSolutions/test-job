<?php

namespace DTApi\Mailers;

interface MailerInterface
{
    public function send($email, $name, $subject, $view, $data);
}