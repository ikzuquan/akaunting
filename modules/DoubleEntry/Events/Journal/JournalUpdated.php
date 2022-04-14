<?php

namespace Modules\DoubleEntry\Events\Journal;

use Illuminate\Queue\SerializesModels;

class JournalUpdated
{
    use SerializesModels;

    /**
     * The journal instance.
     *
     * @var \Modules\DoubleEntry\Models\Journal
     */
    public $journal;

    public $request;

    /**
     * Create a new event instance.
     *
     * @param \Modules\DoubleEntry\Models\Journal $journal
     * @param $request
     * @return void
     */
    public function __construct($journal, $request)
    {
        $this->journal = $journal;
        $this->request = $request;
    }
}
