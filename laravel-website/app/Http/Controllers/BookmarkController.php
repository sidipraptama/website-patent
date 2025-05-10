<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    protected $apiBaseUrl;
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        // Set the API base URL from environment or default to Docker internal address
        $this->apiBaseUrl = env('API_BASE_URL', 'http://172.17.0.1:8001');

        // Set the API URL for by_ids dynamically
        $this->apiUrl = $this->apiBaseUrl . '/api/patents/by_ids';

        // Set the API key from environment
        $this->apiKey = env('FAST_API_KEY');
    }

    public function index()
    {
        return view('bookmarks');
    }

    // Mengambil data paten yang sudah dibookmark user
    public function fetchBookmarks()
    {
        $userId = Auth::id();

        // Ambil semua patent_id yang telah dibookmark user
        $bookmarkedPatentIds = Bookmark::where('user_id', $userId)
            ->pluck('patent_id')
            ->toArray();

        // Jika tidak ada bookmark, kembalikan response kosong
        if (empty($bookmarkedPatentIds)) {
            return response()->json([
                'total' => 0,
                'results' => [],
            ]);
        }

        // Panggil FastAPI untuk mengambil detail paten berdasarkan ID
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($this->apiUrl, [
                    'patent_ids' => $bookmarkedPatentIds,
                ]);

        // Jika request ke FastAPI gagal
        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch bookmarked patents.'], 500);
        }

        $data = json_decode($response->body(), true);

        // Validasi hasil
        if (!isset($data['results']) || !is_array($data['results'])) {
            return response()->json(['error' => 'No bookmark details found.'], 404);
        }

        // Tambahkan flag is_bookmarked ke semua hasil
        foreach ($data['results'] as &$result) {
            if (isset($result['_source'])) {
                $result['_source']['is_bookmarked'] = true;
            }
        }

        // Kembalikan hanya bagian '_source' agar lebih clean
        $cleanedResults = array_map(function ($result) {
            return $result['_source'];
        }, $data['results']);

        return response()->json([
            'total' => count($cleanedResults),
            'results' => $cleanedResults,
        ]);
    }
}
