<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Support\Mail\MailService to($user)
 * @method static \Zuno\Support\Mail\MailService send(\Zuno\Support\Mail\Mailable $mailable)
 * @method static \Zuno\Support\Mail\MailService cc(string|array $cc)
 * @method static \Zuno\Support\Mail\MailService bcc(string|array $bcc)
 * @see \Zuno\Support\Mail\MailService
 */

use Zuno\Facade\BaseFacade;

class Mail extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'mail';
    }
}
