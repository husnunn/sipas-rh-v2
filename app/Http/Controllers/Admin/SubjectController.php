<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(): Response
    {
        $subjects = Subject::query()
            ->orderByDesc('id')
            ->paginate(25);

        return Inertia::render('Admin/Subjects/Index', [
            'subjects' => $subjects,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Subjects/Form', [
            'mode' => 'create',
            'subject' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Subject::create([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.subjects.index')
            ->with('flash', ['type' => 'success', 'message' => 'Mata pelajaran berhasil ditambahkan.']);
    }

    public function show(Subject $subject): Response
    {
        $subject->load('teachers.user');

        return Inertia::render('Admin/Subjects/Show', [
            'subject' => $subject,
        ]);
    }

    public function edit(Subject $subject): Response
    {
        return Inertia::render('Admin/Subjects/Form', [
            'mode' => 'edit',
            'subject' => $subject,
        ]);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:subjects,code,' . $subject->id],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $subject->update([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.subjects.index')
            ->with('flash', ['type' => 'success', 'message' => 'Mata pelajaran berhasil diperbarui.']);
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return redirect()->route('admin.subjects.index')
            ->with('flash', ['type' => 'success', 'message' => 'Mata pelajaran berhasil dihapus.']);
    }
}
