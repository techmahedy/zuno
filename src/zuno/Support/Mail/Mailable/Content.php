<?php

namespace Zuno\Support\Mail\Mailable;

class Content
{
    /**
     * Constructor for the Content class.
     *
     * @param string $view The view file for the email content.
     * @param mixed $data The data to pass to the view.
     */
    public function __construct(public string $view = '', public mixed $data = '') {}

    /**
     * Returns a new instance of the Content class with the same view and data.
     *
     * This method is useful for method chaining or creating a copy of the content.
     *
     * @return self A new instance of the Content class.
     */
    public function content(): self
    {
        return new self(
            view: $this->view,
            data: $this->data ?? null
        );
    }
}
