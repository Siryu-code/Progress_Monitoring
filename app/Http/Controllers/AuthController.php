<?php

namespace App\Http\Controllers;

use App\Models\Developer;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validasi input — semua wajib diisi
        $request->validate([
            'username'     => 'required|string',
            'password'     => 'required|string',
            'project_code' => 'required|string',
        ]);

        // Langkah 1: Cari developer berdasarkan username
        $developer = Developer::where('username', $request->username)->first();

        // Jika username tidak ditemukan, atau password salah — tolak
        if (!$developer || !Hash::check($request->password, $developer->password)) {
            return response()->json([
                'message' => 'Username atau password salah.'
            ], 401);
        }

        // Langkah 2: Cari project berdasarkan project_code
        $project = Project::where('project_code', $request->project_code)->first();

        // Jika project tidak ditemukan — tolak
        if (!$project) {
            return response()->json([
                'message' => 'Kode project tidak ditemukan.'
            ], 404);
        }

        // Langkah 3: Cek apakah developer ini punya akses ke project tersebut
        // Menggunakan relasi many-to-many dari tabel developer_project
        $hasAccess = $developer->projects()->where('project_id', $project->id)->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Kamu tidak memiliki akses ke project ini.'
            ], 403);
        }

        // Semua lolos — buat token Sanctum untuk developer ini
        // Token diberi nama project_code supaya mudah diidentifikasi
        $token = $developer->createToken($project->project_code)->plainTextToken;

        // Return token + data project untuk ditampilkan di confirm modal
        return response()->json([
            'message'      => 'Login berhasil.',
            'token'        => $token,
            'developer'    => [
                'id'       => $developer->id,
                'username' => $developer->username,
            ],
            'project'      => [
                'id'           => $project->id,
                'project_name' => $project->project_name,
                'project_code' => $project->project_code,
                'status'       => $project->status,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        // Hapus token yang sedang dipakai sekarang
        // currentAccessToken() = token yang dikirim di request ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.'
        ], 200);
    }
}