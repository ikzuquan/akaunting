<?php

namespace Modules\DoubleEntry\Http\Requests;

use App\Abstracts\Http\FormRequest;

class Journal extends FormRequest
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
        $attachment = 'nullable';

        if ($this->files->get('attachment')) {
            $attachment = 'mimes:' . config('filesystems.mimes') . '|between:0,' . config('filesystems.max_size') * 1024;
        }

        return [
            'paid_at' => 'required|date',
            'reference' => 'nullable|string|max:191',
            'description' => 'required',
            'journal_number' => 'required',
            'basis' => 'required',
            'currency_code' => 'required|string|currency',
            'currency_rate' => 'required|gt:0',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|integer',
            'items.*.debit' => 'required|double-entry-amount',
            'items.*.credit' => 'required|double-entry-amount',
            'attachment.*' => $attachment,
        ];
    }

    public function messages()
    {
        return [
            'items.*.account_id.required' => trans('validation.required', ['attribute' => mb_strtolower(trans('double-entry::general.account'))]),
        ];
    }
}
