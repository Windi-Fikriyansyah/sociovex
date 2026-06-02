<?php

namespace App\Http\Controllers;

use App\Models\ScheduledPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function index()
    {
        $tenant = Auth::user()->tenant;

        $scheduledPosts = ScheduledPost::where('tenant_id', $tenant->id)
            ->orderBy('scheduled_at')
            ->get();

        // Format for calendar events (JSON)
        $events = $scheduledPosts->map(function ($post) {
            $platforms = is_array($post->platforms) ? implode(', ', $post->platforms) : '';
            $statusColor = match($post->status) {
                'published' => '#28a745',
                'failed'    => '#dc3545',
                default     => '#4680ff',
            };
            return [
                'id'    => $post->id,
                'title' => substr($post->caption ?? 'Post', 0, 50) . (strlen($post->caption ?? '') > 50 ? '...' : ''),
                'start' => $post->scheduled_at->toIso8601String(),
                'color' => $statusColor,
                'extendedProps' => [
                    'status'    => $post->status,
                    'platforms' => $platforms,
                    'caption'   => $post->caption,
                ],
            ];
        });

        return view('calendar.index', compact('events', 'tenant', 'scheduledPosts'));
    }

    public function update(Request $request, ScheduledPost $scheduledPost)
    {
        $tenant = Auth::user()->tenant;

        if ($scheduledPost->tenant_id !== $tenant->id) {
            abort(403);
        }

        $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $scheduledPost->update(['scheduled_at' => $request->scheduled_at]);

        return response()->json(['success' => true, 'message' => 'Jadwal berhasil diperbarui.']);
    }

    public function destroy(ScheduledPost $scheduledPost)
    {
        $tenant = Auth::user()->tenant;

        if ($scheduledPost->tenant_id !== $tenant->id) {
            abort(403);
        }

        $scheduledPost->delete();

        return back()->with('success', 'Jadwal berhasil dihapus.');
    }

    public function edit(ScheduledPost $scheduledPost)
    {
        $tenant = Auth::user()->tenant;

        if ($scheduledPost->tenant_id !== $tenant->id) {
            abort(403);
        }

        return view('calendar.edit', compact('scheduledPost', 'tenant'));
    }

    public function reschedule(Request $request, ScheduledPost $scheduledPost)
    {
        $tenant = Auth::user()->tenant;

        if ($scheduledPost->tenant_id !== $tenant->id) {
            abort(403);
        }

        $request->validate([
            'scheduled_at' => ['required', 'date'],
        ]);

        $scheduledPost->update(['scheduled_at' => $request->scheduled_at]);

        return back()->with('success', 'Jadwal berhasil diperbarui.');
    }
}
