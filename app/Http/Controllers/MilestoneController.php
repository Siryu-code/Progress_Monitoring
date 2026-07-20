<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function update(Request $request, Milestone $milestone)
    {
        // Cek akses developer ke project milik milestone ini
        $developer = $request->user();
        $hasAccess = $developer->projects()
            ->where('project_id', $milestone->project_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Kamu tidak memiliki akses ke project ini.'
            ], 403);
        }

        // ===== CYCLE LOGIC =====
        // Status sekarang → status berikutnya
        // 0 (belum) → 1 (on going) → 2 (selesai) → 0 (belum) → ...
        // Modulo 3 adalah kuncinya:
        // 0 % 3 = 0, tapi kita tambah 1 dulu → (0+1) % 3 = 1 ✅
        // 1 % 3 = 1, tapi kita tambah 1 dulu → (1+1) % 3 = 2 ✅
        // 2 % 3 = 2, tapi kita tambah 1 dulu → (2+1) % 3 = 0 ✅
        $nextStatus = ($milestone->status + 1) % 3;

        $milestone->update(['status' => $nextStatus]);

        // ===== AUTO-UPDATE PROJECT STATUS =====
        // Ambil project yang terkait dengan milestone ini
        $project = $milestone->project;

        // Ambil total milestone dan yang sudah selesai (status 2)
        $totalMilestones     = $project->milestones()->count();
        $completedMilestones = $project->milestones()->where('status', 2)->count();

        // Cek apakah semua milestone sudah selesai
        if ($totalMilestones > 0 && $completedMilestones === $totalMilestones) {
            // Semua selesai — update project status ke 2
            $project->update(['status' => 2]);
        } else {
            // Belum semua selesai — pastikan project status kembali ke 1 (on going)
            // Ini penting! Kalau developer "un-complete" milestone yang tadinya bikin 100%
            // status project harus balik ke on going, bukan tetap 2
            $project->update(['status' => 1]);
        }

        // Hitung ulang progress percentage untuk dikirim ke frontend
        $progress = $totalMilestones > 0
            ? round(($completedMilestones / $totalMilestones) * 100)
            : 0;

        return response()->json([
            'message'  => 'Status milestone berhasil diupdate.',
            'milestone' => [
                'id'     => $milestone->id,
                'status' => $nextStatus,
            ],
            'project_status'      => $project->status,
            'progress' => $progress,
        ], 200);
    }
}