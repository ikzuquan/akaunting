<?php

namespace Modules\DoubleEntry\Providers;

use Illuminate\Support\ServiceProvider;
use Validator;

class Validation extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $request = $this->app->request->input();
        //$currency_code = setting('default.currency');

        Validator::extend('DoubleEntryAmount', function ($attribute, $value, $parameters, $validator) use (&$amount, $request) {
            if (empty($request)) {
                $request = request();
            }

            $status = false;
            $debit = $credit = 0;
            $attributes = explode('.', $attribute);
            $currency_code = $request['currency_code'] ? $request['currency_code'] : 'USD';

            if (!empty($request['items'][$attributes[1]]['debit'])) {
                $debit = money($request['items'][$attributes[1]]['debit'], $currency_code)->getAmount();
            }

            if (!empty($request['items'][$attributes[1]]['credit'])) {
                $credit = money($request['items'][$attributes[1]]['credit'], $currency_code)->getAmount();
            }

            if (!empty($debit) || !empty($credit)) {
                $status = true;
            }

            // validation to prevent out of range error on DB
            $parts = preg_split('/[.,]/', $debit);

            if (strlen($parts[0]) > 11) {
                $status = false;
            }

            $parts = preg_split('/[.,]/', $credit);

            if (strlen($parts[0]) > 11) {
                $status = false;
            }

            return $status;
        }, trans('validation.custom.invalid_amount', ['attribute' => $amount]));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
