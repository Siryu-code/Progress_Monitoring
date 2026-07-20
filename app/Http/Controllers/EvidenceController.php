<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use App\Models\Timeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    public function index(Timeline $timeline)
    {
        // Public endpoint — dipanggil saat user klik tombol "Lihat Bukti"
        // Gambar baru di-load saat ini, bukan saat halaman pertama dibuka (lazy load)
        $evidences = $timeline->evidence()->get()->map(function ($evidence) {
            return [
                'id'        => $evidence->id,
                'image_url' => asset('storage/' . $evidence->image_path),
            ];
        });

        return response()->json([
            'evidences' => $evidences,
        ], 200);
    }

    public function store(Request $request, Timeline $timeline)
    {
        // Validasi — wajib ada gambar, bisa lebih dari 1
        $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

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

        $saved = [];

        // Simpan setiap gambar ke storage dan buat record evidence-nya
        foreach ($request->file('images') as $image) {
            // Simpan file ke storage/app/public/evidence
            $path = $image->store('evidence', 'public');

            // Buat record di DB, terhubung ke timeline ini
            $evidence = $timeline->evidence()->create([
                'image_path' => $path,
            ]);

            // Kumpulkan semua evidence yang baru disimpan
            $saved[] = [
                'id'        => $evidence->id,
                'image_url' => asset('storage/' . $evidence->image_path),
            ];
        }

        return response()->json([
            'message'   => 'Gambar berhasil ditambahkan.',
            'evidences' => $saved,
        ], 201);
    }

    public function destroy(Request $request, Evidence $evidence)
    {
        // Cek akses developer ke project melalui timeline milik evidence ini
        // Evidence → Timeline → Project
        $developer = $request->user();
        $hasAccess = $developer->projects()
            ->where('project_id', $evidence->timeline->project_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Kamu tidak memiliki akses ke project ini.'
            ], 403);
        }

        // Hapus file fisik dari storage dulu
        Storage::disk('public')->delete($evidence->image_path);

        // Baru hapus record dari DB
        $evidence->delete();

        return response()->json([
            'message' => 'Gambar berhasil dihapus.'
        ], 200);
    }
}