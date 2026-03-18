<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $query = Feedback::withoutGlobalScopes()->with(['tenant', 'user']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $query->orderByRaw("FIELD(status, 'new', 'reviewed')")
              ->orderByDesc('created_at');

        $feedback = $query->paginate(25);
        $newCount = Feedback::withoutGlobalScopes()->where('status', 'new')->count();

        // Load selected item if ?id= is present
        $selectedItem = null;
        if ($id = $request->input('id')) {
            $selectedItem = Feedback::withoutGlobalScopes()->with(['tenant', 'user'])->find($id);
            if ($selectedItem && $selectedItem->status === 'new') {
                $selectedItem->update(['status' => 'reviewed']);
                logActivity('updated', "Reviewed feedback: {$selectedItem->subject}", $selectedItem);
            }
        }

        return view('super-admin.feedback.index', compact('feedback', 'newCount', 'selectedItem'));
    }

    public function show(int $id)
    {
        // Redirect to combined view with ?id= param
        return redirect()->route('super.feedback', ['id' => $id]);
    }

    public function screenshot(int $id, int $index = 0): StreamedResponse
    {
        $item = Feedback::withoutGlobalScopes()->findOrFail($id);

        $screenshots = $item->screenshots();
        $path = $screenshots[$index] ?? null;

        abort_if(!$path, 404);

        $mime = Storage::disk('local')->mimeType($path);

        return response()->streamDownload(
            fn () => print(Storage::disk('local')->get($path)),
            basename($path),
            ['Content-Type' => $mime, 'Content-Disposition' => 'inline']
        );
    }

    public function destroy(int $id)
    {
        $item = Feedback::withoutGlobalScopes()->findOrFail($id);
        logActivity('deleted', "Deleted feedback: {$item->subject}", $item);
        $item->delete();

        return redirect()->route('super.feedback')->with('success', 'Feedback deleted.');
    }
}
