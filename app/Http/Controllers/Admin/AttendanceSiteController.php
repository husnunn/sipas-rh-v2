<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceSiteRequest;
use App\Http\Requests\Admin\UpdateAttendanceSiteRequest;
use App\Models\AttendanceSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceSiteController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();

        return Inertia::render('Admin/AttendanceSites/Index', [
            'sites' => AttendanceSite::query()
                ->with('wifiRules')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status): void {
                    $query->where('is_active', $status === 'active');
                })
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'filters' => [
                'search' => $search,
                'status' => in_array($status, ['active', 'inactive'], true) ? $status : '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/AttendanceSites/Form', [
            'mode' => 'create',
            'site' => null,
        ]);
    }

    public function store(StoreAttendanceSiteRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $site = AttendanceSite::create([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        foreach ($validated['wifi_rules'] ?? [] as $rule) {
            $site->wifiRules()->create([
                ...$rule,
                'is_active' => $rule['is_active'] ?? true,
            ]);
        }

        return redirect()->route('admin.attendance-sites.index')
            ->with('flash', ['type' => 'success', 'message' => 'Titik absensi berhasil dibuat.']);
    }

    public function show(AttendanceSite $attendanceSite): Response
    {
        $attendanceSite->load('wifiRules');

        return Inertia::render('Admin/AttendanceSites/Show', [
            'site' => $attendanceSite,
        ]);
    }

    public function edit(AttendanceSite $attendanceSite): Response
    {
        $attendanceSite->load('wifiRules');

        return Inertia::render('Admin/AttendanceSites/Form', [
            'mode' => 'edit',
            'site' => $attendanceSite,
        ]);
    }

    public function update(UpdateAttendanceSiteRequest $request, AttendanceSite $attendanceSite): RedirectResponse
    {
        $validated = $request->validated();
        $attendanceSite->update([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $attendanceSite->wifiRules()->delete();
        foreach ($validated['wifi_rules'] ?? [] as $rule) {
            $attendanceSite->wifiRules()->create([
                ...$rule,
                'is_active' => $rule['is_active'] ?? true,
            ]);
        }

        return redirect()->route('admin.attendance-sites.index')
            ->with('flash', ['type' => 'success', 'message' => 'Titik absensi berhasil diperbarui.']);
    }

    public function destroy(AttendanceSite $attendanceSite): RedirectResponse
    {
        $attendanceSite->delete();

        return redirect()->route('admin.attendance-sites.index')
            ->with('flash', ['type' => 'success', 'message' => 'Titik absensi berhasil dihapus.']);
    }

    public function toggleActive(AttendanceSite $attendanceSite): RedirectResponse
    {
        $attendanceSite->update(['is_active' => ! $attendanceSite->is_active]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Status titik absensi diperbarui.']);
    }
}
