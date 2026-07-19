<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function access(Request $request)
    {
        // Validasi — project_code wajib diisi
        $request->validate([
            'project_code' => 'required|string',
        ]);

        // Cari project berdasarkan project_code yang diinput client
        $project = Project::where('project_code', $request->project_code)->first();

        // Jika tidak ditemukan — tolak
        if (!$project) {
            return response()->json([
                'message' => 'Kode project tidak ditemukan.'
            ], 404);
        }

        // Return data project untuk ditampilkan di confirm modal
        // "Apakah anda ingin masuk ke project <project_name>?"
        return response()->json([
            'message' => 'Kode project valid.',
            'project' => [
                'id'           => $project->id,
                'project_name' => $project->project_name,
                'project_code' => $project->project_code,
            ],
        ], 200);
    }

    public function dashboard(Project $project)
    {
        // Laravel otomatis cari project by ID dari URL {project}
        // Ini namanya Route Model Binding — kalau tidak ditemukan, otomatis 404

        // Ambil semua milestone milik project ini, urut berdasarkan kolom `order`
        $milestones = $project->milestones()->orderBy('order')->get();

        // Hitung progress percentage
        $totalMilestones     = $milestones->count();
        $completedMilestones = $milestones->where('status', 2)->count();

        // Jaga-jaga kalau milestone masih kosong — hindari division by zero
        $progressPercentage = $totalMilestones > 0
            ? round(($completedMilestones / $totalMilestones) * 100)
            : 0;

        // Status project dibaca langsung dari DB
        // Nanti MilestoneController yang akan update kolom ini saat progress 100%
        return response()->json([
            'project' => [
                'id'           => $project->id,
                'project_name' => $project->project_name,
                'project_code' => $project->project_code,
                'status'       => $project->status,
            ],
            'milestones'          => $milestones,
            'progress_percentage' => $progressPercentage,
        ], 200);
    }
}