<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Services\TagSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, TagSuggestionService $service): JsonResponse
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:30']]);

        return response()->json(['data' => $service->suggest($data['q'] ?? '')]);
    }
}
