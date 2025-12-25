<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SwaggerController extends Controller
{
    /**
     * Display the Swagger UI interface
     */
    public function index()
    {
        // Only available in local environment
        if (!app()->environment('local')) {
            abort(404);
        }

        return view('swagger.index');
    }

    /**
     * Serve the OpenAPI YAML file
     */
    public function yaml()
    {
        // Only available in local environment
        if (!app()->environment('local')) {
            abort(404);
        }

        $yamlPath = base_path('docs/openapi/openapi.yaml');

        if (!file_exists($yamlPath)) {
            abort(404, 'OpenAPI specification not found');
        }

        return response()->file($yamlPath, [
            'Content-Type' => 'application/x-yaml',
        ]);
    }
}
