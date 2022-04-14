<?php

namespace Modules\DoubleEntry\Http\Requests;

use App\Abstracts\Http\FormRequest;

class Setting extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'accounts_receivable' => 'required|integer',
            'accounts_payable' => 'required|integer',
            'accounts_sales' => 'required|integer',
            'accounts_expenses' => 'required|integer',
            'accounts_sales_discount' => 'required|integer',
            'accounts_purchase_discount' => 'required|integer',
            'accounts_owners_contribution' => 'required|integer',
            'types_bank' => 'required|integer',
            'types_tax' => 'required|integer',
            'journal_number_prefix' => 'required',
            'journal_number_digit' => 'required',
            'journal_number_next' => 'required',
        ];
    }
}
