<?php

namespace App\Http\Controllers;

use App\Models\DraftPatent;
use App\Models\DraftPatentImage;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DraftPatentController extends Controller
{
    public function index()
    {
        return view('draftPatent');
    }

    public function show($id)
    {
        $draft = DraftPatent::with('similarityCheck')->findOrFail($id);
        return view('draftPatentDetail', compact('draft'));
    }

    public function update(Request $request, $id)
    {
        // Validasi input: semua field boleh kosong
        $request->validate([
            'title' => 'nullable|string|max:255',
            'technical_field' => 'nullable|string',
            'background' => 'nullable|string',
            'summary' => 'nullable|string',
            'description' => 'nullable|string',
            'claims' => 'nullable|string',
            'abstract' => 'nullable|string',
        ]);

        // Temukan draft berdasarkan ID
        $draft = DraftPatent::findOrFail($id);

        // Update field yang ada
        $draft->update([
            'title' => $request->input('title'),
            'technical_field' => $request->input('technical_field'),
            'background' => $request->input('background'),
            'summary' => $request->input('summary'),
            'description' => $request->input('description'),
            'claims' => $request->input('claims'),
            'abstract' => $request->input('abstract'),
        ]);

        return response()->json(['success' => true, 'message' => 'Draft berhasil diupdate.']);
    }

    // DraftPatentController.php
    public function save(Request $request, $id)
    {
        // Validasi input untuk field yang spesifik
        $request->validate([
            'field' => 'required|string',
            'content' => 'nullable|string',
        ]);

        // Temukan draft berdasarkan ID
        $draft = DraftPatent::findOrFail($id);

        // Dapatkan nama field dan content
        $field = $request->input('field');
        $content = $request->input('content');

        // Pastikan field yang dimaksud ada dalam model (untuk keamanan)
        if (in_array($field, ['title', 'technical_field', 'background', 'summary', 'description', 'claims', 'abstract'])) {
            // Update field yang sesuai dengan nilai content
            $draft->update([$field => $content]);

            return response()->json(['success' => true, 'message' => 'Draft berhasil diupdate.']);
        }

        return response()->json(['success' => false, 'message' => 'Field tidak valid.']);
    }

    public function getData()
    {
        // Ambil drafts beserta similarityCheck menggunakan eager loading
        $drafts = DraftPatent::with('similarityCheck') // Memuat relasi similarityCheck
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json($drafts);
    }

    public function delete($id)
    {
        $draft = DraftPatent::find($id);

        if (!$draft) {
            return response()->json(['message' => 'Draft tidak ditemukan.'], 404);
        }

        // Hanya user yang membuat draft yang bisa menghapusnya
        if ($draft->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Hapus draft
        $draft->delete();

        return response()->json(['message' => 'Draft berhasil dihapus.']);
    }

    public function duplicate($id)
    {
        $draft = DraftPatent::find($id);

        if (!$draft) {
            return response()->json(['message' => 'Draft tidak ditemukan.'], 404);
        }

        // Hanya user yang membuat draft yang bisa menggandakannya
        if ($draft->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Gandakan draft (salin data, kecuali ID dan created_at/updated_at)
        $newDraft = $draft->replicate();
        $newDraft->save();

        return response()->json(['message' => 'Draft berhasil digandakan.', 'new_draft_id' => $newDraft->draft_id]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'check_id' => 'required|exists:similarity_checks,check_id',
        ]);

        $user_id = auth()->id();
        $check_id = $request->check_id;

        // Cari draft terakhir dengan title yang dimulai dengan 'Untitled'
        $lastDraft = DraftPatent::where('user_id', $user_id)
            ->where('title', 'like', 'Untitled%')
            ->latest()
            ->first();

        // Tentukan title untuk draft baru
        if ($lastDraft) {
            // Jika ada draft yang berjudul 'Untitled [angka]', ambil angka terakhir dan tambahkan 1
            preg_match('/Untitled-(\d+)/', $lastDraft->title, $matches);
            $newTitle = 'Untitled-' . ((int) ($matches[1] ?? 0) + 1);
        } else {
            // Jika belum ada draft, gunakan 'Untitled' sebagai title pertama
            $newTitle = 'Untitled';
        }

        // Buat draft baru dengan title yang sudah ditentukan
        $draft = DraftPatent::create([
            'user_id' => $user_id,
            'check_id' => $check_id,
            'title' => $newTitle, // Set title yang sudah ditentukan
        ]);

        return response()->json([
            'message' => 'Draft patent berhasil dibuat.',
            'redirect_url' => route('draft-patent.detail', $draft->draft_id),
        ]);
    }

    public function storeImage(Request $request)
    {
        // Cek apakah file dikirim atau tidak (karena terlalu besar)
        if (!$request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'Ukuran gambar melebihi batas maksimum (2MB).',
            ], 413);
        }

        try {
            $request->validate([
                'image' => 'required|image|max:2048',
                'draft_id' => 'required|exists:draft_patents,draft_id',
            ]);

            $path = $request->file('image')->store('patent-images', 'public');

            $image = DraftPatentImage::create([
                'draft_id' => $request->draft_id,
                'idx' => DraftPatentImage::where('draft_id', $request->draft_id)->count(),
                'file' => $path,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gambar berhasil diupload!',
                'image_url' => asset('storage/' . $path),
                'image_id' => $image->image_id,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupload gambar.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroyImage($id)
    {
        $image = DraftPatentImage::findOrFail($id);

        if (Storage::disk('public')->exists($image->file)) {
            Storage::disk('public')->delete($image->file);
        }

        $image->delete();

        // Mengembalikan response JSON untuk AJAX
        return response()->json(['success' => true]);
    }
}
