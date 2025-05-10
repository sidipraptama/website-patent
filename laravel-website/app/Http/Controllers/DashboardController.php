<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $apiBaseUrl;
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        // Set the API base URL from environment or default to Docker internal address
        $this->apiBaseUrl = env('API_BASE_URL', 'http://172.17.0.1:8001');

        // Set the statistics URL based on the base URL
        $this->apiUrl = $this->apiBaseUrl . '/api/patents/statistics';

        // Set the API key from environment
        $this->apiKey = env('FAST_API_KEY');
    }

    public function index()
    {
        $statistics = $this->fetchPatentStatistics();

        return view('dashboard', [
            'statistics' => $statistics,
        ]);
    }

    public function fetchPatentStatistics()
    {
        try {
            $response = Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->apiUrl);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::warning('FastAPI responded with non-200 status', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to connect to FastAPI service', [
                'message' => $e->getMessage(),
            ]);
        }

        // Default fallback if API fails
        return [
            'total' => 0,
            'by_patent_type' => [],
        ];
    }

    public function fetchPatentStatisticsYearly()
    {
        try {
            $response = Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get("{$this->apiUrl}/yearly");

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::warning('FastAPI responded with non-200 status for yearly stats', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to connect to FastAPI service for yearly stats', [
                'message' => $e->getMessage(),
            ]);
        }

        // Fallback jika gagal
        return [];
    }
}
