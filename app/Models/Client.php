<?php

namespace App\Models;

/**
 * Backwards-compatible alias for the renamed Contact model.
 *
 * Legacy code still references App\Models\Client, so we expose
 * this thin wrapper that extends the new Contact model and
 * keeps the table mapping pointed at `contacts`.
 */
class Client extends Contact
{
    /**
     * Explicitly set the table so older code that relied on
     * the `clients` table continues to work with the renamed
     * `contacts` table.
     *
     * @var string
     */
    protected $table = 'contacts';
}
