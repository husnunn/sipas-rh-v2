<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicCalendarEventRequest;
use App\Http\Requests\Admin\UpdateAcademicCalendarEventRequest;
use App\Models\AcademicCalendarEvent;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicCalendarEventController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/AcademicCalendarEvents/Index', [
            'events' => AcademicCalendarEvent::query()->orderByDesc('start_date')->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/AcademicCalendarEvents/Form', [
            'mode' => 'create',
            'event' => null,
            'eventTypes' => $this->eventTypes(),
        ]);
    }

    public function store(StoreAcademicCalendarEventRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        AcademicCalendarEvent::create([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
            'allow_attendance' => $validated['allow_attendance'] ?? false,
            'override_schedule' => $validated['override_schedule'] ?? false,
        ]);

        return redirect()->route('admin.academic-calendar-events.index')
            ->with('flash', ['type' => 'success', 'message' => 'Event kalender akademik berhasil ditambahkan.']);
    }

    public function show(AcademicCalendarEvent $academicCalendarEvent): Response
    {
        return Inertia::render('Admin/AcademicCalendarEvents/Show', [
            'event' => $academicCalendarEvent,
        ]);
    }

    public function edit(AcademicCalendarEvent $academicCalendarEvent): Response
    {
        return Inertia::render('Admin/AcademicCalendarEvents/Form', [
            'mode' => 'edit',
            'event' => $academicCalendarEvent,
            'eventTypes' => $this->eventTypes(),
        ]);
    }

    public function update(UpdateAcademicCalendarEventRequest $request, AcademicCalendarEvent $academicCalendarEvent): RedirectResponse
    {
        $validated = $request->validated();
        $academicCalendarEvent->update([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
            'allow_attendance' => $validated['allow_attendance'] ?? false,
            'override_schedule' => $validated['override_schedule'] ?? false,
        ]);

        return redirect()->route('admin.academic-calendar-events.index')
            ->with('flash', ['type' => 'success', 'message' => 'Event kalender akademik berhasil diperbarui.']);
    }

    public function destroy(AcademicCalendarEvent $academicCalendarEvent): RedirectResponse
    {
        $academicCalendarEvent->delete();

        return redirect()->route('admin.academic-calendar-events.index')
            ->with('flash', ['type' => 'success', 'message' => 'Event kalender akademik berhasil dihapus.']);
    }

    /**
     * @return array<int, string>
     */
    private function eventTypes(): array
    {
        return [
            'national_holiday',
            'school_holiday',
            'school_event',
            'exam',
            'special_date',
        ];
    }
}
