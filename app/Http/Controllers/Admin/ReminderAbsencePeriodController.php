<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReminderAbsencePeriod;
use Illuminate\Http\Request;

class ReminderAbsencePeriodController extends Controller
{
    public function index()
    {
        return view('admin.reminder_absence_periods', [
            'periods' => ReminderAbsencePeriod::query()
                ->orderByDesc('date_from')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'employee_tag' => ['required', 'string', 'max:191'],
        ]);

        $tag = trim($data['employee_tag']);
        $normalized = ReminderAbsencePeriod::normalizeUsername($tag);
        if ($normalized === '') {
            return back()->withErrors(['employee_tag' => 'Укажите тег или логин сотрудника (например @ivan).'])->withInput();
        }

        ReminderAbsencePeriod::query()->create([
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'employee_tag' => $tag,
            'username_normalized' => $normalized,
        ]);

        return redirect()->route('admin.absence-periods')->with('status', 'Период добавлен.');
    }

    public function destroy(ReminderAbsencePeriod $reminderAbsencePeriod)
    {
        $reminderAbsencePeriod->delete();

        return redirect()->route('admin.absence-periods')->with('status', 'Запись удалена.');
    }
}
