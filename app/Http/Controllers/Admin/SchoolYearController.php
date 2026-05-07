<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesAdminDeletes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSchoolYearRequest;
use App\Http\Requests\Admin\UpdateSchoolYearRequest;
use App\Models\SchoolYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SchoolYearController extends Controller
{
    use HandlesAdminDeletes;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:50'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));

        $rows = SchoolYear::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/SchoolYears/Index', [
            'schoolYears' => $rows,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/SchoolYears/Form', [
            'mode' => 'create',
            'schoolYear' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSchoolYearRequest $request): RedirectResponse
    {
        SchoolYear::query()->create($request->validated());

        return redirect()
            ->route('admin.school-years.index')
            ->with('flash', ['type' => 'success', 'message' => 'Tahun ajaran berhasil ditambahkan.']);
    }

    /**
     * Display the specified resource.
     */
    public function show(SchoolYear $schoolYear): Response
    {
        return Inertia::render('Admin/SchoolYears/Show', [
            'schoolYear' => $schoolYear,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SchoolYear $schoolYear): Response
    {
        return Inertia::render('Admin/SchoolYears/Form', [
            'mode' => 'edit',
            'schoolYear' => $schoolYear,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSchoolYearRequest $request, SchoolYear $schoolYear): RedirectResponse
    {
        $schoolYear->update($request->validated());

        return redirect()
            ->route('admin.school-years.index')
            ->with('flash', ['type' => 'success', 'message' => 'Tahun ajaran berhasil diperbarui.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SchoolYear $schoolYear): RedirectResponse
    {
        return $this->tryDelete(
            function () use ($schoolYear): void {
                $schoolYear->delete();
            },
            'admin.school-years.index',
            ['type' => 'success', 'message' => 'Tahun ajaran berhasil dihapus.'],
            'Gagal menghapus tahun ajaran. Silakan coba lagi.',
        );
    }

    public function setActive(SchoolYear $schoolYear): RedirectResponse
    {
        if ($schoolYear->is_active) {
            $schoolYear->update(['is_active' => false]);

            return redirect()
                ->route('admin.school-years.index')
                ->with('flash', ['type' => 'success', 'message' => 'Tahun ajaran dinonaktifkan.']);
        }

        DB::transaction(function () use ($schoolYear): void {
            SchoolYear::query()
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $schoolYear->update(['is_active' => true]);
        });

        return redirect()
            ->route('admin.school-years.index')
            ->with('flash', ['type' => 'success', 'message' => 'Tahun ajaran aktif berhasil diperbarui.']);
    }
}
