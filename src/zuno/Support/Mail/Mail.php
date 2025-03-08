<?php

namespace Zuno\Support\Mail;

use Zuno\Support\Mail\MailDriverInterface;
use Zuno\Support\Mail\Driver\SmtpMailDriver;
use Zuno\Support\Mail\Mailable\View;
use App\Models\User;

/**
 * The Mail class is responsible for sending emails using a specified mail driver.
 * It provides a fluent interface for setting up email details (e.g., recipients, CC, BCC, attachments)
 * and sending the email using the configured driver.
 */
class Mail
{
    /**
     * The mail driver used to send emails.
     *
     * @var MailDriverInterface
     */
    private $driver;

    /**
     * The Mailable object representing the email message.
     *
     * @var Mailable
     */
    private $message;

    /**
     * Constructor for the Mail class.
     *
     * @param MailDriverInterface $driver The mail driver to use for sending emails.
     */
    public function __construct(MailDriverInterface $driver)
    {
        $this->driver = $driver;
        $this->message = new Mailable();
    }

    /**
     * Creates a new Mail instance with the specified driver.
     *
     * This method is useful for explicitly setting a custom mail driver.
     *
     * @param MailDriverInterface $driver The mail driver to use.
     * @return self A new instance of the Mail class.
     */
    public static function driver(MailDriverInterface $driver)
    {
        return new self($driver);
    }

    /**
     * Creates a new Mail instance and sets the primary recipient.
     *
     * This method initializes the Mail instance with the default driver and sets the "to" address
     * and "from" address based on the provided user and configuration.
     *
     * @param User $user The user to send the email to.
     * @return self A new instance of the Mail class.
     */
    public static function to(User $user)
    {
        // Create a new Mail instance with the resolved driver
        $mail = new self(self::resolveDriver());

        // Set the "to" address and name
        $mail->message->to = ['address' => $user->email, 'name' => $user?->name];

        // Set the "from" address and name from the configuration
        $mail->message->from = [
            'address' => config('mail.from.address'),
            'name' => config('mail.from.name'),
        ];

        return $mail;
    }

    /**
     * Sends the email using the provided Mailable object.
     *
     * This method sets the email subject, body, CC, BCC, and attachments based on the Mailable object
     * and delegates the actual sending to the mail driver.
     *
     * @param Mailable $mailable The Mailable object containing email details.
     * @return mixed The result of the email sending operation.
     * @throws \Exception If an attachment file is not found.
     */
    public function send(Mailable $mailable)
    {
        $this->message->subject = $mailable->subject()?->subject;
        $this->message->body =  View::render($mailable);
        $this->message->cc = array_merge($this->message->cc, $mailable->cc);
        $this->message->bcc = array_merge($this->message->bcc, $mailable->bcc);

        $attachment = $mailable->attachment() ?? [];

        if (isset($attachment[0])) {
            foreach ($attachment as $filePath) {
                $filePath = base_path(removeBaseUrl($filePath));
                if (!file_exists($filePath)) {
                    throw new \Exception("$filePath not found");
                }
                $this->message->attachments[] = [
                    'path' => $filePath,
                    'name' => basename($filePath),
                    'mime' => mime_content_type($filePath),
                ];
            }
        } else {
            foreach ($attachment as $filePath => $fileDetails) {
                $filePath = base_path(removeBaseUrl($filePath));
                if (!file_exists($filePath)) {
                    throw new \Exception("$filePath not found");
                }
                $this->message->attachments[] = [
                    'path' => $filePath,
                    'name' => $fileDetails['as'] ?? basename($filePath),
                    'mime' => $fileDetails['mime'] ?? mime_content_type($filePath),
                ];
            }
        }

        return $this->driver->send($this->message);
    }

    /**
     * Adds CC (carbon copy) recipients to the email.
     *
     * This method accepts a single email address (string) or an array of email addresses.
     *
     * @param string|array $cc The CC recipient(s).
     * @return self The current Mail instance for method chaining.
     */
    public function cc($cc)
    {
        if (is_string($cc)) {
            $this->message->cc[] = $cc;
        } else {
            $this->message->cc = array_merge($this->message->cc, is_array($cc) ? $cc : [$cc]);
        }
        return $this;
    }

    /**
     * Adds BCC (blind carbon copy) recipients to the email.
     *
     * This method accepts a single email address (string) or an array of email addresses.
     *
     * @param string|array $bcc The BCC recipient(s).
     * @return self The current Mail instance for method chaining.
     */
    public function bcc($bcc)
    {
        if (is_string($bcc)) {
            $this->message->bcc[] = $bcc;
        } else {
            $this->message->bcc = array_merge($this->message->bcc, is_array($bcc) ? $bcc : [$bcc]);
        }
        return $this;
    }

    /**
     * Resolves the mail driver based on the application configuration.
     *
     * This method reads the mail configuration and instantiates the appropriate mail driver.
     *
     * @return MailDriverInterface The resolved mail driver.
     * @throws \Exception If the mailer is not supported.
     */
    private static function resolveDriver()
    {
        // Get the default mailer from the configuration
        $mailer = config('mail.default');

        // Get the configuration for the specified mailer
        $config = config('mail.mailers.' . $mailer);

        // Instantiate the appropriate mail driver based on the mailer
        switch ($mailer) {
            case 'smtp':
                // SMTP driver configuration
                $config = [
                    "transport" => env('MAIL_MAILER'),
                    "url" => env('MAIL_URL'),
                    "host" =>  env('MAIL_HOST') ?? '127.0.0.1',
                    "port" => env('MAIL_PORT') ?? 2525,
                    "encryption" => env('MAIL_ENCRYPTION') ?? 'tls',
                    "username" => env('MAIL_USERNAME') ?? null,
                    "password" => env('MAIL_PASSWORD') ?? null,
                    "timeout" => null,
                    "local_domain" => "localhost",
                ];
                return new SmtpMailDriver($config);
            case 'log':
                // TODO: Implement log mail driver
                break;
            default:
                throw new \Exception("Unsupported mailer: $mailer");
        }
    }
}
