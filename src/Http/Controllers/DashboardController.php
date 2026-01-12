<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class DashboardController extends Controller
{
    /**
     * Display the Laravel Schema dashboard.
     */
    public function index(): View
    {
        return view('laravel-schema::layout', [
            'schemaScriptVariables' => $this->scriptVariables(),
        ]);
    }

    /**
     * Get the variables to pass to the frontend JavaScript.
     *
     * @return array<string, mixed>
     */
    private function scriptVariables(): array
    {
        return [
            'path' => config('schema.path', 'schema'),
            'apiUrl' => url(config('schema.path', 'schema').'/api'),
        ];
    }
}
