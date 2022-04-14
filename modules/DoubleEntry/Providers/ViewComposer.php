<?php

namespace Modules\DoubleEntry\Providers;

use Illuminate\Support\ServiceProvider;
use View;

class ViewComposer extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function boot()
    {
        View::composer([
            'components.documents.form.line-item'
        ], 'Modules\DoubleEntry\Http\ViewComposers\DocumentItem');

        View::composer([
            'common.items.create',
			'inventory::items.create',
            'common.items.edit',
			'inventory::items.edit',
        ], 'Modules\DoubleEntry\Http\ViewComposers\Items');

        View::composer([
            'sales.revenues.create',
            'sales.revenues.edit',
            'purchases.payments.create',
            'purchases.payments.edit',
            'modals.documents.payment',
        ], 'Modules\DoubleEntry\Http\ViewComposers\Transactions');

        View::composer([
            'components.transactions.show.transaction'
        ], 'Modules\DoubleEntry\Http\ViewComposers\JournalShow');

        View::composer([
            'components.transactions.show.footer',
            'components.documents.show.footer',
        ], 'Modules\DoubleEntry\Http\ViewComposers\Journals');

        // Apps
        View::composer([
            'receipt::receipt.edit'
        ], 'Modules\DoubleEntry\Http\ViewComposers\ReceiptInput');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
