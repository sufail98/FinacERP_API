<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ContactMail extends Mailable
{
    public $data;
    public $filePath;
    public $fileName;

    public function __construct($data, $filePath = null, $fileName = null)
    {
        $this->data = $data;
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function build()
    {
        $email = $this->subject('New Contact Message')
            ->html("
                <h2>New Contact Message</h2>
                <p><strong>Name:</strong> {$this->data['name']}</p>
                <p><strong>Email:</strong> {$this->data['email']}</p>
                <p><strong>Message:</strong></p>
                <p>{$this->data['message']}</p>
            ");

        if ($this->filePath) {
            $email->attach(storage_path('app/public/' . $this->filePath), [
                'as' => $this->fileName,
            ]);
        }

        return $email;
    }
}