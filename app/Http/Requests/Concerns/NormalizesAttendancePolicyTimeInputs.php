<?php

namespace App\Http\Requests\Concerns;

trait NormalizesAttendancePolicyTimeInputs
{
    /**
     * Normalize `type="time"` payloads (`H:i:s`) to `H:i` expected by validation.
     *
     * @return list<string>
     */
    protected function attendancePolicyTimeFieldNames(): array
    {
        return [
            'check_in_open_at',
            'check_in_on_time_until',
            'check_in_close_at',
            'check_out_open_at',
            'check_out_close_at',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach ($this->attendancePolicyTimeFieldNames() as $field) {
            $value = $this->input($field);

            if (! is_string($value) || $value === '') {
                continue;
            }

            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $matches)) {
                $merged[$field] = sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
            }
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }
}
