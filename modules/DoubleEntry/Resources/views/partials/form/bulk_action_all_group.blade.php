@stack('bulk_action_all_input_start')

    <div class="custom-control custom-checkbox">
        <input class="custom-control-input" id="table-check-all-{{ $attributes['group'] }}" type="checkbox"
            v-model="{{ !empty($attributes['v-model']) ? $attributes['v-model'] : 'bulk_action.select_all' }}"
            @click="onSelectAll({{ !empty($attributes['group']) ? $attributes['group'] : undefined }})">
        <label class="custom-control-label" for="table-check-all-{{ $attributes['group'] }}"></label>
    </div>

@stack('bulk_action_all_input_end')
