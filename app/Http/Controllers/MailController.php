<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email',
            'message'  => 'required|string',
            'document' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ]);

        $filePath = null;
        $fileName = null;
        $branchName = $request->branchName;
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('documents', $fileName, 'public');
        }

        try {
            Mail::send([], [], function ($message) use ($validated, $filePath, $fileName, $branchName) {


                $subject = "Thank you for contacting {$branchName}";

                $html = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                </head>
                <body style="font-family:Arial,Helvetica,sans-serif;background:#f5f5f5;padding:20px;">
                    <table width="600" align="center" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #dddddd;border-radius:6px;">
                        <tr>
                            <td style="padding:25px;">

                                <h2 style="margin-top:0;color:#2c3e50;">
                                    Thank You for Contacting ' . e($branchName) . '.
                                </h2>

                                <p>Hello <strong>' . e($validated['name']) . '</strong>,</p>

                                <p>
                                    Thank you for contacting ' . e($branchName) . '.
                                    We have successfully received your enquiry.
                                </p>

                                <p><strong>Your Details</strong></p>

                                <table cellpadding="6">
                                    <tr>
                                        <td><strong>Name</strong></td>
                                        <td>' . e($validated['name']) . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email</strong></td>
                                        <td>' . e($validated['email']) . '</td>
                                    </tr>
                                    <tr>
                                        <td valign="top"><strong>Message</strong></td>
                                        <td>' . nl2br(e($validated['message'])) . '</td>
                                    </tr>
                                </table>

                                <br>

                                <p>
                                    Our team will review your message and get back to you as soon as possible.
                                </p>

                                <br>

                                <p>
                                    Regards,<br>
                                    <strong>' . e($branchName) . '</strong>
                                </p>

                            </td>
                        </tr>
                    </table>
                </body>
                </html>';

                $message->from(
                    config('mail.from.address'),
                    config('mail.from.name')
                );

                $message->to($validated['email']);

                $message->subject($subject);

                $message->priority(3);

                $message->html($html);

                $message->text(
                    "Thank you for contacting Finace ERP.\n\n" .
                        "Name: {$validated['name']}\n" .
                        "Email: {$validated['email']}\n\n" .
                        "Message:\n{$validated['message']}\n\n" .
                        "We will contact you shortly.\n\n" .
                        $branchName
                );

                if ($filePath) {
                    $message->attach(
                        storage_path('app/public/' . $filePath),
                        [
                            'as' => $fileName,
                        ]
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully.'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to send email.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}