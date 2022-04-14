<?php

namespace Modules\DoubleEntry\Events\Journal;

use Illuminate\Queue\SerializesModels;

class JournalCreated
{
    use SerializesModels;

    /**
     * The journal instance.
     *
     * @var \Modules\DoubleEntry\Models\Journal
     */
    public $journal;

    /**
     * Create a new event instance.
     *
     * @param \Modules\DoubleEntry\Models\Journal $journal
     * @return void
     */
    public function __construct($journal)
    {
        $this->journal = $journal;
    }
}
