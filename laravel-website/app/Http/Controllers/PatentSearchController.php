<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class PatentSearchController extends Controller
{
    protected $apiBaseUrl = env('API_BASE_URL', 'http://host.docker.internal:8000');

    protected $apiUrl = $this->apiBaseUrl . '/api/patents/search';

    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('FAST_API_KEY');
    }

    // Menampilkan halaman pencarian paten
    public function index()
    {
        return view('patentSearch');
    }

    public function search(Request $request)
    {
        $query = $request->input('query', '');
        $page = $request->input('page', 1);
        $size = $request->input('size', 12);
        $sortBy = $request->input('sort_by', 'relevance'); // Default to 'relevance' if not provided
        $userId = Auth::id(); // Ambil ID user yang sedang login

        // Validasi parameter sort_by jika perlu (misalnya, bisa 'date', 'relevance', etc.)
        $validSortByOptions = ['relevance', 'newest', 'oldest'];
        if (!in_array($sortBy, $validSortByOptions)) {
            return response()->json(['error' => 'Invalid sort_by parameter.'], 400);
        }

        // Kirim request ke FastAPI dengan sort_by
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($this->apiUrl, [
                    'query' => $query,
                    'page' => $page,
                    'size' => $size,
                    'sort_by' => $sortBy,  // Include sort_by in the request
                ]);

        // Jika request gagal, kirim error response
        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch patents.'], 500);
        }

        $data = json_decode($response->body(), true);

        // Pastikan hasilnya valid
        if (!isset($data['results'])) {
            return response()->json(['error' => 'No results found.'], 404);
        }

        // Ambil semua patent_id dari hasil FastAPI
        $patentIds = array_column(array_column($data['results'], '_source'), 'patent_id');

        // Ambil daftar patent_id yang sudah di-bookmark oleh user
        $bookmarkedPatents = Bookmark::where('user_id', $userId)
            ->whereIn('patent_id', $patentIds)
            ->pluck('patent_id')
            ->toArray();

        // Tambahkan status `is_bookmarked` ke setiap hasil
        foreach ($data['results'] as &$result) {
            $patent = &$result['_source'];
            $patent['is_bookmarked'] = in_array($patent['patent_id'], $bookmarkedPatents);
        }

        return response()->json([
            'total' => $data['total'] ?? 0,
            'page' => $data['page'] ?? $page,
            'size' => $data['size'] ?? $size,
            'results' => $data['results'],
        ]);
    }

    public function bookmark(Request $request)
    {
        $request->validate([
            'patent_id' => 'required|string',
        ]);

        $user = Auth::user();

        $existingBookmark = Bookmark::where('user_id', $user->id)
            ->where('patent_id', $request->patent_id)
            ->first();

        if ($existingBookmark) {
            // Jika belum ada flag confirm_removal, minta konfirmasi dulu
            if (!$request->has('confirm_removal')) {
                return response()->json(['status' => 'confirm-removal']);
            }

            // Kalau sudah ada flag confirm_removal, hapus bookmark
            $existingBookmark->delete();
            return response()->json(['status' => 'removed']);
        } else {
            // Tambahkan bookmark
            Bookmark::create([
                'user_id' => $user->id,
                'patent_id' => $request->patent_id,
            ]);
            return response()->json(['status' => 'added']);
        }
    }
}
