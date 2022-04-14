<?php

namespace Modules\DoubleEntry\Jobs\Journal;

use App\Abstracts\Job;
use App\Traits\Relationships;
use Modules\DoubleEntry\Events\Journal\JournalUpdated;
use Modules\DoubleEntry\Models\Ledger;

class UpdateJournalEntry extends Job
{
    use Relationships;

    protected $request;

    /**
     * The journal instance.
     *
     * @var \Modules\DoubleEntry\Models\Journal
     */
    protected $journalEntry;

    /**
     * Create a new job instance.
     *
     * @param \Modules\DoubleEntry\Models\Journal $journalEntry
     * @param $request
     * @return void
     */
    public function __construct($journalEntry, $request)
    {
        $this->journalEntry = $journalEntry;
        $this->request = $this->getRequestInstance($request);
    }

    /**
     * Execute the job.
     *
     * @return \Modules\DoubleEntry\Models\Journal
     */
    public function handle()
    {
        \DB::transaction(function () {
            $input = $this->request->input();
            $amount = 0;
            $input['amount'] = $amount;
            $ledgers = [];

            $this->journalEntry->update($input);

            foreach ($input['items'] as $item) {
                $ledger = Ledger::find($item['id']);

                if ($ledger) {
                    if (!empty($item['debit'])) {
                        $ledger->update([
                            'company_id' => $this->journalEntry->company_id,
                            'account_id' => $item['account_id'],
                            'issued_at' => $this->journalEntry->paid_at,
                            'entry_type' => 'item',
                            'debit' => $item['debit'],
                            'notes' => $item['notes'] ?? null,
                        ]);

                        $amount += $item['debit'];
                    } else {
                        $ledger->update([
                            'company_id' => $this->journalEntry->company_id,
                            'account_id' => $item['account_id'],
                            'issued_at' => $this->journalEntry->paid_at,
                            'entry_type' => 'item',
                            'credit' => $item['credit'],
                            'notes' => $item['notes'] ?? null,
                        ]);
                    }

                    array_push($ledgers, $ledger->id);

                    continue;
                }

                if (!empty($item['debit'])) {
                    $ledger = $this->journalEntry->ledger()->create([
                        'company_id' => $this->journalEntry->company_id,
                        'account_id' => $item['account_id'],
                        'issued_at' => $this->journalEntry->paid_at,
                        'entry_type' => 'item',
                        'debit' => $item['debit'],
                        'notes' => $item['notes'] ?? null,
                    ]);

                    $amount += $item['debit'];
                } else {
                    $ledger = $this->journalEntry->ledger()->create([
                        'company_id' => $this->journalEntry->company_id,
                        'account_id' => $item['account_id'],
                        'issued_at' => $this->journalEntry->paid_at,
                        'entry_type' => 'item',
                        'credit' => $item['credit'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                array_push($ledgers, $ledger->id);
            }

            foreach ($this->journalEntry->ledgers as $ledger) {
                if (!in_array($ledger->id, $ledgers)) {
                    $ledger->delete();
                }
            }

            $this->journalEntry->amount = $amount;
            $this->journalEntry->save();

            // Upload attachment
            if ($this->request->file('attachment')) {
                $this->deleteMediaModel($this->journalEntry, 'attachment', $this->request);

                foreach ($this->request->file('attachment') as $attachment) {
                    $media = $this->getMedia($attachment, 'journal_entries');

                    $this->journalEntry->attachMedia($media, 'attachment');
                }
            } elseif (!$this->request->file('attachment') && $this->journalEntry->attachment) {
                $this->deleteMediaModel($this->journalEntry, 'attachment', $this->request);
            }
        });

        event(new JournalUpdated($this->journalEntry, $this->request));

        return $this->journalEntry;
    }
}
