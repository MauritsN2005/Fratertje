<?php

namespace App\Events;

use App\Models\Invoice;
use App\Models\Member;

class InvoiceRequested
{
    public ?Invoice $invoice = null;

    public function __construct(public Member $member, public int $year, public string $kind = 'debit') {}
}
