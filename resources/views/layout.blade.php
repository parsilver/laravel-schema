<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Laravel Schema</title>

    @if(file_exists(public_path('vendor/laravel-schema/css/app.css')))
        <link rel="stylesheet" href="{{ asset('vendor/laravel-schema/css/app.css') }}">
    @else
        <style>
            /* Fallback styles when assets not published */
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background-color: #f3f4f6;
                color: #1f2937;
                min-height: 100vh;
            }
            #app {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 2rem;
            }
            .fallback {
                background: white;
                border-radius: 0.5rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                padding: 2rem;
                max-width: 600px;
                text-align: center;
            }
            .fallback h1 { font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem; }
            .fallback p { color: #6b7280; margin-bottom: 1rem; }
            .fallback code {
                display: block;
                background: #f3f4f6;
                padding: 0.75rem;
                border-radius: 0.375rem;
                font-size: 0.875rem;
                margin: 0.5rem 0;
            }
            .fallback a {
                display: inline-block;
                background: #4f46e5;
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                text-decoration: none;
                margin-top: 1rem;
            }
        </style>
    @endif
</head>
<body class="h-full bg-gray-100 dark:bg-gray-900">
    <div id="app" class="h-full">
        @unless(file_exists(public_path('vendor/laravel-schema/js/app.js')))
            <div class="fallback">
                <h1>Laravel Schema</h1>
                <p>Frontend assets need to be published. Run:</p>
                <code>php artisan vendor:publish --tag=laravel-schema-assets</code>
                <p style="margin-top: 1rem;">Or build the assets:</p>
                <code>cd vendor/farzai/laravel-schema && npm install && npm run build</code>
                <a href="{{ url(config('schema.path', 'schema') . '/api/status') }}">View API Status</a>
            </div>
        @endunless
    </div>

    <script>
        window.LaravelSchema = @json($schemaScriptVariables ?? []);
    </script>

    @if(file_exists(public_path('vendor/laravel-schema/js/app.js')))
        <script src="{{ asset('vendor/laravel-schema/js/app.js') }}"></script>
    @endif
</body>
</html>
