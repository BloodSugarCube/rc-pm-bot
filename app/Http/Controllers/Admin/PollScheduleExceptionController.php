<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PollScheduleException;
use Illuminate\Http\Request;

class PollScheduleExceptionController extends Controller
{
    public function index()
    {
        return view('admin.schedule_exceptions', [
            'exceptions' => PollScheduleException::query()->orderByDesc('exception_date')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'exception_date' => ['required', 'date', 'unique:poll_schedule_exceptions,exception_date'],
            'send_polls' => ['required', 'in:0,1'],
        ]);

        PollScheduleException::query()->create([
            'exception_date' => $data['exception_date'],
            'send_polls' => (bool) (int) $data['send_polls'],
        ]);

        return redirect()->route('admin.schedule-exceptions')->with('status', 'День добавлен.');
    }

    public function destroy(PollScheduleException $pollScheduleException)
    {
        $pollScheduleException->delete();

        return redirect()->route('admin.schedule-exceptions')->with('status', 'Запись удалена.');
    }
}
