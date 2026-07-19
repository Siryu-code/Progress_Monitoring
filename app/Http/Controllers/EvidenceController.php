<?php

namespace App\Http\Controllers;

use App\Models\Timeline;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TimelineController extends Controller
{
    public function index(Project $project)
    {
        // Public endpoint — tidak perlu auth
        // Urut dari terbaru ke terlama (created_at DESC)
        // Paginate 10 per load — ini yang handle lazy loading di frontend
        $timelines = $project->timelines()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($timelines, 200);
    }

    public function store(Request $request, Project $project)
    {
        // Validasi — judul dan deskripsi wajib, gambar opsional
        $request->validate([
            'title'       => 'required|string|max:100',
            'description' => 'required|string',
            'images'      => 'nullable|array',
            'images.*'    => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Cek apakah developer yang login punya akses ke project ini
        // Lewat relasi many-to-many di tabel developer_project
        $developer = $request->user();
        $hasAccess = $developer->projects()
            ->where('project_id', $project->id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Kamu tidak memiliki akses ke project ini.'
            ], 403);
        }

        // Buat timeline — project_id otomatis terisi karena pakai relationship
        $timeline = $project->timelines()->create([
            'developer_id' => $developer->id,
            'title'        => $request->title,
            'description'  => $request->description,
        ]);

        // Jika ada gambar yang diupload, simpan satu per satu sebagai evidence
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                // Simpan ke storage/app/public/evidence
                $path = $image->store('evidence', 'public');

                // Buat record evidence yang terhubung ke timeline ini
                $timeline->evidence()->create([
                    'image_path' => $path,
                ]);
            }
        }

        // Load evidence yang baru disimpan supaya ikut ke-return ke frontend
        $timeline->load('evidence');

        return response()->json([
            'message'  => 'Timeline berhasil ditambahkan.',
            'timeline' => $timeline,
        ], 201);
    }

    public function update(Request $request, Timeline $timeline)
    {
        // Inline edit — hanya title dan description yang bisa diubah
        $request->validate([
            'title'       => 'required|string|max:100',
            'description' => 'required|string',
        ]);

        // Cek akses developer ke project milik timeline ini
        // Pakai project_id yang ada di record timeline
        $developer = $request->user();
        $hasAccess = $developer->projects()
            ->where('project_id', $timeline->project_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Kamu tidak memiliki akses ke project ini.'
            ], 403);
        }

        // Update hanya field yang diizinkan
        $timeline->update([
            'title'       => $request->title,
            'description' => $request->description,
        ]);

        return response()->json([
            'message'  => 'Timeline berhasil diupdate.',
            'timeline' => $timeline,
        ], 200);
    }

    public function destroy(Request $request, Timeline $timeline)
    {
        // Cek akses developer ke project milik timeline ini
        $developer = $request->user();
        $hasAccess = $developer->projects()
            ->where('project_id', $timeline->project_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Kamu tidak memiliki akses ke project ini.'
            ], 403);
        }

        // Ambil semua evidence milik timeline ini
        $evidences = $timeline->evidence;

        // Hapus file fisik dari storage satu per satu
        foreach ($evidences as $evidence) {
            Storage::disk('public')->delete($evidence->image_path);
        }

        // Hapus semua record evidence dari DB
        $timeline->evidence()->delete();

        // Baru hapus timelinenya
        $timeline->delete();

        return response()->json([
            'message' => 'Timeline berhasil dihapus.'
        ], 200);
    }
}