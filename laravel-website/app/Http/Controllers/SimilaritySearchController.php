<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\DraftPatent;
use App\Models\SimilarityCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Str;

class SimilaritySearchController extends Controller
{
    protected $apiUrl = 'http://host.docker.internal:8000/api/similarity';
    protected $apiUrlByIds = 'http://host.docker.internal:8000/api/patents/by_ids';
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('FAST_API_KEY');
    }

    // Menampilkan halaman similarity check (jika perlu view)
    public function index()
    {
        return view('similaritySearch');
    }

    // Endpoint untuk memproses similarity check
    public function search(Request $request)
    {
        try {
            $validated = $request->validate([
                'abstract' => 'required|string',
                'limit' => 'nullable|integer|min:1|max:50',
            ]);

            $abstract = $validated['abstract'];
            $limit = $validated['limit'] ?? 50;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(60)
                ->post($this->apiUrl, [
                    'abstract' => $abstract,
                    'limit' => $limit,
                ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memeriksa kemiripan.',
                    'status' => $response->status()
                ], $response->status());
            }

            $responseData = $response->json();
            $results = $responseData['data'] ?? [];

            $userId = Auth::id();
            $bookmarkedIds = Bookmark::where('user_id', $userId)->pluck('patent_id')->toArray();

            foreach ($results as &$result) {
                $result['is_bookmarked'] = in_array($result['patent_id'], $bookmarkedIds);
            }

            // Simpan ke history
            $check = SimilarityCheck::create([
                'user_id' => $userId,
                'input_text' => $abstract,
            ]);

            // Simpan ke check_results
            $checkResults = [];
            foreach ($results as $result) {
                if (!isset($result['score']) || !is_numeric($result['score'])) {
                    continue; // skip data tidak valid
                }

                $checkResults[] = [
                    'check_id' => $check->check_id,
                    'patent_id' => $result['patent_id'],
                    'similarity_score' => $result['score'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            \DB::table('check_results')->insert($checkResults);

            return response()->json([
                'success' => true,
                'check_id' => $check->check_id,
                'data' => $results
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Similarity Search Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function listChecks()
    {
        $userId = Auth::id();

        $checks = SimilarityCheck::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get(['check_id', 'input_text', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $checks,
        ]);
    }

    public function results($checkId)
    {
        $userId = Auth::id();

        // Cek apakah similarity check ini milik user tersebut
        $check = SimilarityCheck::where('check_id', $checkId)
            ->where('user_id', $userId)
            ->first();

        if (!$check) {
            return response()->json([
                'success' => false,
                'message' => 'Similarity check tidak ditemukan atau bukan milik user ini.'
            ], 404);
        }

        // Ambil hasil dari tabel check_results
        $results = \DB::table('check_results')
            ->where('check_id', $checkId)
            ->orderByDesc('similarity_score')
            ->get(['patent_id', 'similarity_score']);

        if ($results->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        // Ambil semua patent_id dari hasil
        $patentIds = $results->pluck('patent_id')->toArray();
        $patentIds = array_map('strval', $patentIds);

        // Ambil data bookmark user
        $bookmarked = Bookmark::where('user_id', $userId)
            ->whereIn('patent_id', $patentIds)
            ->pluck('patent_id')
            ->toArray();

        // Ambil data paten dari FastAPI
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($this->apiUrlByIds, [
                    'patent_ids' => $patentIds
                ]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => $response->json()
            ], 500);
        }

        $patentData = collect($response->json('results'));

        // Gabungkan similarity_score dan is_bookmarked ke data paten
        $finalResults = $results->map(function ($result) use ($patentData, $bookmarked) {
            $detail = $patentData->firstWhere('_source.patent_id', $result->patent_id);
            if (!$detail)
                return null;

            $source = $detail['_source'];
            $source['similarity_score'] = $result->similarity_score;
            $source['is_bookmarked'] = in_array($result->patent_id, $bookmarked);

            return $source;
        })->filter();

        return response()->json([
            'success' => true,
            'similarity_check' => $check,
            'check_results' => $finalResults->values(),
            'has_draft' => DraftPatent::where('check_id', $checkId)->exists(),
        ]);
    }

    public function checkResultsOnly($checkId)
    {
        $userId = Auth::id();

        // Cek apakah similarity check ini milik user tersebut
        $check = SimilarityCheck::where('check_id', $checkId)
            ->where('user_id', $userId)
            ->first();

        if (!$check) {
            return response()->json([
                'success' => false,
                'message' => 'Similarity check tidak ditemukan atau bukan milik user ini.'
            ], 404);
        }

        // Ambil hasil dari tabel check_results
        $results = \DB::table('check_results')
            ->where('check_id', $checkId)
            ->orderByDesc('similarity_score')
            ->get(['patent_id', 'similarity_score']);

        if ($results->isEmpty()) {
            return response()->json([
                'success' => true,
                'check_results' => [],
            ]);
        }

        // Ambil semua patent_id dari hasil
        $patentIds = $results->pluck('patent_id')->map(fn($id) => (string) $id)->toArray();

        // Ambil data paten dari FastAPI
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($this->apiUrlByIds, [
                    'patent_ids' => $patentIds
                ]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => $response->json(),
            ], 500);
        }

        $patentData = collect($response->json('results'));

        // Gabungkan similarity_score ke data paten
        $finalResults = $results->map(function ($result) use ($patentData) {
            $detail = $patentData->firstWhere('_source.patent_id', $result->patent_id);
            if (!$detail)
                return null;

            $source = $detail['_source'];
            $source['similarity_score'] = $result->similarity_score;

            return $source;
        })->filter();

        return response()->json([
            'success' => true,
            'check_results' => $finalResults->values(),
        ]);
    }
}
