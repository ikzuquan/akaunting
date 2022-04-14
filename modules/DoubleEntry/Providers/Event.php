<?php

namespace Modules\DoubleEntry\Providers;

use Modules\DoubleEntry\Listeners\DocumentCloned;
use Modules\DoubleEntry\Listeners\TransactionCloned;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class Event extends ServiceProvider
{
    /**
     * The event listener mappings for the module.
     *
     * @var array
     */
    protected $listen = [
        'cloner::cloned: App\Models\Document\Document' => [
            DocumentCloned::class,
        ],
        'cloner::cloned: App\Models\Banking\Transaction' => [
            TransactionCloned::class,
        ],
    ];

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return true;
    }

    /**
     * Get the listener directories that should be used to discover events.
     *
     * @return array
     */
    protected function discoverEventsWithin()
    {
        return [
            __DIR__ . '/../Listeners',
        ];
    }
}
