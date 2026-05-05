<?php

namespace App\Http\Controllers\Concerns;

use App\Support\DatabaseDeleteHumanizer;
use Illuminate\Http\RedirectResponse;
use Throwable;

trait HandlesAdminDeletes
{
    /**
     * @param  callable(): void  $operation
     * @param  array{type: 'success', message: string}|array{type: 'error', message: string}  $successFlash
     */
    protected function tryDelete(
        callable $operation,
        string $redirectRouteName,
        array $successFlash,
        string $fallbackErrorMessage,
    ): RedirectResponse {
        try {
            $operation();

            return redirect()->route($redirectRouteName)->with('flash', $successFlash);
        } catch (Throwable $e) {
            return redirect()->route($redirectRouteName)->with(
                'flash',
                DatabaseDeleteHumanizer::flash($e, $fallbackErrorMessage),
            );
        }
    }
}
