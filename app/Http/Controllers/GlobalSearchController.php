<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlobalSearchController extends Controller
{
    public function index(Request $request, GlobalSearchService $search): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json([
                'recent' => $search->recent(),
                'minimum_characters' => (int) config('global-search.minimum_characters', 2),
            ]);
        }

        return response()->json([
            'query' => $term,
            'results' => $search->search($term),
            'all_results_url' => route('global-search.results', ['q' => $term]),
        ]);
    }

    public function remember(Request $request, GlobalSearchService $search): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $search->remember($validated['q']);

        return response()->json(['saved' => true]);
    }

    public function results(Request $request, GlobalSearchService $search): View
    {
        $term = trim((string) $request->query('q', ''));
        $results = $search->search($term, (int) config('global-search.page_limit', 50));

        if ($term !== '') {
            $search->remember($term);
        }

        return view('search.results', compact('term', 'results'));
    }
}
