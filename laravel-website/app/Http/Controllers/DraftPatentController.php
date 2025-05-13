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

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'check_id' => 'required|exists:similarity_checks,check_id',
    //     ]);

    //     $user_id = auth()->id();
    //     $check_id = $request->check_id;

    //     // Cari draft terakhir dengan title yang dimulai dengan 'Untitled'
    //     $lastDraft = DraftPatent::where('user_id', $user_id)
    //         ->where('title', 'like', 'Untitled%')
    //         ->latest()
    //         ->first();

    //     // Tentukan title untuk draft baru
    //     if ($lastDraft) {
    //         // Jika ada draft yang berjudul 'Untitled [angka]', ambil angka terakhir dan tambahkan 1
    //         preg_match('/Untitled-(\d+)/', $lastDraft->title, $matches);
    //         $newTitle = 'Untitled-' . ((int) ($matches[1] ?? 0) + 1);
    //     } else {
    //         // Jika belum ada draft, gunakan 'Untitled' sebagai title pertama
    //         $newTitle = 'Untitled';
    //     }

    //     // Buat draft baru dengan title yang sudah ditentukan
    //     $draft = DraftPatent::create([
    //         'user_id' => $user_id,
    //         'check_id' => $check_id,
    //         'title' => $newTitle, // Set title yang sudah ditentukan
    //     ]);

    //     return response()->json([
    //         'message' => 'Draft patent berhasil dibuat.',
    //         'redirect_url' => route('draft-patent.detail', $draft->draft_id),
    //     ]);
    // }

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
            preg_match('/Untitled-(\d+)/', $lastDraft->title, $matches);
            $newTitle = 'Untitled-' . ((int) ($matches[1] ?? 0) + 1);
        } else {
            $newTitle = 'Untitled';
        }

        // Data dummy seolah hasil AI
        $dummyContent = [
            'technical_field' => 'Invensi ini terkait dengan bidang teknologi informasi, khususnya sistem berbasis kecerdasan buatan (Artificial Intelligence/Ai) dan komputasi awan (cloud computing) yang digunakan untuk pengolahan data paten, pengukuran similaritas semantik, dan penyusunan otomatis dokumen paten.',

            'background' => 'Proses pendaftaran paten merupakan tahapan krusial dalam perlindungan kekayaan intelektual yang memerlukan kecermatan tinggi. Untuk menghasilkan dokumen paten yang kuat dan memiliki peluang besar untuk disetujui, inventor dan profesional kekayaan intelektual (IP) perlu melakukan pencarian dokumen pembanding (prior art search) yang komprehensif serta menyusun dokumen teknis yang memenuhi kaidah hukum yang berlaku di berbagai yurisdiksi. Namun, tahapan ini sering kali memakan waktu yang lama dan membutuhkan biaya besar.

        Beberapa sistem telah dikembangkan untuk membantu pencarian prior art. Contohnya, PatentsView (US Patent and Trademark Office) menyediakan antarmuka untuk eksplorasi metadata paten. Selain itu, Google Patents menawarkan fitur pencarian teks penuh yang cukup canggih. Namun, kedua sistem ini tetap mengandalkan masukan manual dan belum mampu melakukan pencarian semantik berbasis AI yang mempertimbangkan konteks teknis yang kompleks dari deskripsi ide paten.

        Untuk menjawab kebutuhan pencarian semantik, penelitian seperti yang dilakukan oleh Lee, D., Kim, J., & Lee, J. (2020) telah mengusulkan penerapan model deep learning seperti BERT untuk representasi teks paten (PatentBERT). Mereka menunjukkan bahwa model ini dapat meningkatkan akurasi pencarian prior art dibandingkan pendekatan berbasis kata kunci tradisional.

        Selain pencarian, penyusunan dokumen paten juga menghadapi tantangan besar. Beberapa platform seperti Specifio telah mulai mengembangkan solusi penyusunan paten otomatis berbasis AI yang menghasilkan bagian-bagian spesifikasi berdasarkan masukan pengguna. Namun, solusi ini masih terbatas dalam hal integrasi end-to-end yang mencakup seluruh alur kerja paten mulai dari input ide hingga pencarian prior art dan drafting final.

        IBM juga telah mengembangkan solusi otomasi pemrosesan dokumen berbasis AI, seperti yang terdapat dalam IBM Automation Document Processing. Solusi ini menggabungkan AI dengan deep learning dan alat low-code untuk menghilangkan pemrosesan dokumen manual. Meskipun teknologi ini berfokus pada dokumen bisnis secara umum, pendekatannya menunjukkan bahwa AI memiliki potensi untuk mengotomatisasi penyusunan dokumen yang memerlukan struktur hukum yang ketat, termasuk paten.

        Tantangan tambahan adalah integrasi antarmuka pengguna, penyimpanan data yang aman, dan skalabilitas lintas perangkat. Solusi yang menggunakan cloud computing memungkinkan penyimpanan data embedding paten dan metadata pada server yang mendukung pemrosesan paralel serta kolaborasi real-time. Pendekatan ini telah digunakan oleh sistem seperti Microsoft Azure Cognitive Search dan Elastic Cloud, meskipun tidak secara spesifik dioptimalkan untuk proses pendaftaran paten.

        Dengan mempertimbangkan semua perkembangan tersebut, saat ini belum ada solusi yang sepenuhnya mengintegrasikan seluruh tahapan—mulai dari input ide, konversi embedding berbasis AI, pencocokan similaritas semantik, seleksi referensi yang adaptif, penyusunan draft paten otomatis, hingga integrasi API untuk pengajuan e-filing. Kekosongan teknologi ini menjadi celah inovatif yang signifikan.

        Oleh karena itu, invensi ini dikembangkan untuk menyediakan metode terintegrasi berbasis AI dan cloud computing yang mengotomatisasi seluruh alur kerja pendaftaran paten. Sistem ini menggabungkan berbagai teknologi canggih yang sebelumnya tersebar ke dalam satu solusi modular dan scalable, yang tidak hanya meningkatkan efisiensi tetapi juga akurasi dan kepatuhan hukum dokumen paten yang dihasilkan.',

            'summary' => 'Tujuan invensi ini yaitu menyediakan suatu metode untuk menghasilkan dokumen administratif yang sesuai dengan format e-filing (PDF/XML) dan memungkinkan integrasi langsung dengan sistem pendaftaran paten yang sangat efektif dan terjangkau untuk pengguna.

        Tujuan tersebut dapat dicapai dengan membuat suatu metode terintegrasi berbasis kecerdasan buatan (AI) dan cloud computing untuk mengotomatisasi proses pendaftaran paten, yang terdiri atas tahapan-tahapan yaitu:
        • Mengumpulkan data paten dari sumber eksternal dan mengubah data tersebut menjadi embedding vektor berdimensi tinggi menggunakan model AI berbasis BERT yang dilatih untuk domain paten;
        • Menyimpan embedding vektor ke dalam indeks vektor dan metadata ke dalam basis data terdistribusi di server cloud;
        • Menerima masukan berupa deskripsi ide dan gambar dari pengguna melalui antarmuka daring atau API;
        • Mengonversi deskripsi ide menjadi embedding vektor dan gambar menjadi sketsa teknis digital menggunakan transformer visual berbasis AI;
        • Melakukan pencocokan semantik antara embedding ide pengguna dan embedding paten dalam database menggunakan cosine similarity;
        • Memilih referensi paten dengan threshold adaptif berdasarkan skor similarity, terminologi teknis, dan klasifikasi paten;
        • Secara otomatis menyusun dokumen paten yang mencakup setidaknya judul, bidang teknik, latar belakang, uraian lengkap, klaim, dan gambar teknis; dan
        • Menghasilkan dokumen administratif untuk pengajuan paten dalam format PDF atau XML yang dapat diintegrasikan dengan sistem e-filing.',

            'description' => 'This system comprises a server-side module, a learning model, and a data ingestion pipeline which together allow for dynamic adjustment of system behavior based on user interactions.',

            'claims' => '1. Suatu metode terintegrasi berbasis kecerdasan buatan (AI) dan cloud computing untuk mengotomatisasi proses pendaftaran paten, yang terdiri atas tahapan-tahapan:
        • Mengumpulkan data paten dari sumber eksternal dan mengubah data tersebut menjadi embedding vektor berdimensi tinggi menggunakan model AI berbasis BERT yang dilatih untuk domain paten;
        • Menyimpan embedding vektor ke dalam indeks vektor dan metadata ke dalam basis data terdistribusi di server cloud;
        • Menerima masukan berupa deskripsi ide dan gambar dari pengguna melalui antarmuka daring atau API;
        • Mengonversi deskripsi ide menjadi embedding vektor dan gambar menjadi sketsa teknis digital menggunakan transformer visual berbasis AI;
        • Melakukan pencocokan semantik antara embedding ide pengguna dan embedding paten dalam database menggunakan cosine similarity;
        • Memilih referensi paten dengan threshold adaptif berdasarkan skor similarity, terminologi teknis, dan klasifikasi paten;
        • Secara otomatis menyusun dokumen paten yang mencakup setidaknya judul, bidang teknik, latar belakang, uraian lengkap, klaim, dan gambar teknis; dan
        • Menghasilkan dokumen administratif untuk pengajuan paten dalam format PDF atau XML yang dapat diintegrasikan dengan sistem e-filing.

        2. Metode menurut Klaim 1, di mana embedding vektor disimpan menggunakan sistem indeks vektor Milvus, metadata disimpan di Elasticsearch, dan informasi pengguna disimpan dalam MySQL.

        3. Metode menurut Klaim 1, di mana model AI yang digunakan untuk mengubah deskripsi teks menjadi embedding adalah PatentBERT atau model sejenis yang dilatih secara khusus untuk teks paten.

        4. Metode menurut Klaim 1, di mana proses pencocokan similarity dilakukan dalam kluster cloud yang mendukung pemrosesan paralel untuk meningkatkan kecepatan pencarian.

        5. Metode menurut Klaim 1, di mana seleksi referensi menggunakan threshold adaptif yang dapat disesuaikan secara dinamis berdasarkan konteks bidang teknologi yang dipilih pengguna.

        6. Metode menurut Klaim 1, di mana opsi human-in-the-loop disediakan untuk memungkinkan tinjauan manual terhadap hasil seleksi referensi sebelum drafting otomatis dilanjutkan.

        7. Metode menurut Klaim 1, di mana gambar teknis yang dihasilkan dapat dikustomisasi atau diubah oleh pengguna melalui antarmuka grafis di cloud.',

            'abstract' => 'Invensi ini mengungkapkan suatu metode terintegrasi berbasis kecerdasan buatan (AI) dan komputasi awan (cloud computing) yang dirancang untuk mengotomatisasi seluruh proses awal pendaftaran paten. Metode ini meliputi pembuatan basis data embedding paten dengan menggunakan model AI berbasis BERT yang telah dilatih khusus untuk teks paten, konversi deskripsi ide pengguna menjadi embedding vektor, serta pengolahan gambar ide menjadi sketsa teknis digital.

        Sistem melakukan pencocokan semantik berbasis cosine similarity antara embedding ide pengguna dan embedding paten dalam basis data untuk mengidentifikasi dokumen paten yang relevan. Selanjutnya, sistem menerapkan seleksi referensi adaptif yang mempertimbangkan skor similarity, terminologi teknis, dan klasifikasi paten (CPC/IPC). Dokumen paten disusun secara otomatis meliputi bagian-bagian utama seperti judul, bidang teknik, latar belakang, klaim, uraian lengkap, dan gambar teknis.

        Metode ini juga menghasilkan dokumen administratif yang sesuai dengan format e-filing (PDF/XML) dan memungkinkan integrasi langsung dengan sistem pendaftaran paten. Penggunaan teknologi cloud mendukung skalabilitas, pemrosesan paralel, dan kolaborasi real-time lintas perangkat. Invensi ini memberikan solusi end-to-end yang meningkatkan efisiensi, akurasi, serta kepatuhan hukum dalam penyusunan dokumen paten, yang belum tersedia dalam teknologi sebelumnya.',
        ];

        // Buat draft baru dengan dummy data
        $draft = DraftPatent::create([
            'user_id' => $user_id,
            'check_id' => $check_id,
            'title' => $newTitle,
            'technical_field' => $dummyContent['technical_field'],
            'background' => $dummyContent['background'],
            'summary' => $dummyContent['summary'],
            'description' => $dummyContent['description'],
            'claims' => $dummyContent['claims'],
            'abstract' => $dummyContent['abstract'],
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
