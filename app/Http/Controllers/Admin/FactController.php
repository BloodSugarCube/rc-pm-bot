<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fact;
use Illuminate\Http\Request;

class FactController extends Controller
{
    public function index()
    {
        return view('admin.facts', [
            'facts' => Fact::query()->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        Fact::query()->create([
            'body' => trim($data['body']),
            'is_active' => true,
        ]);

        return redirect()->route('admin.facts')->with('status', 'Факт добавлен.');
    }

    public function toggle(Request $request, Fact $fact)
    {
        $fact->update(['is_active' => ! $fact->is_active]);

        return redirect()->route('admin.facts')->with('status', 'Обновлено.');
    }

    public function destroy(Fact $fact)
    {
        $fact->delete();

        return redirect()->route('admin.facts')->with('status', 'Удалено.');
    }
}
