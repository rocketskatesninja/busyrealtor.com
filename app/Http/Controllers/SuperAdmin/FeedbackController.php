<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeedbackController extends Controller
{
    public function index()
    {
        $feedback = Feedback::withoutGlobalScopes()->with(['tenant', 'user'])
            ->orderByRaw("FIELD(status, 'new', 'reviewed')")
            ->orderByDesc('created_at')
            ->paginate(25);

        $newCount = Feedback::withoutGlobalScopes()->where('status', 'new')->count();

        return view('super-admin.feedback.index', compact('feedback', 'newCount'));
    }

    public function show(int $id)
    {
        $item = Feedback::withoutGlobalScopes()->with(['tenant', 'user'])->findOrFail($id);

        if ($item->status === 'new') {
            $item->update(['status' => 'reviewed']);
        }

        return view('super-admin.feedback.show', compact('item'));
    }

    public function screenshot(int $id): StreamedResponse
    {
        $item = Feedback::withoutGlobalScopes()->findOrFail($id);

        abort_unless($item->hasScreenshot(), 404);

        $path = $item->screenshot_path;
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
        $item->delete();

        return redirect()->route('super.feedback')->with('success', 'Feedback deleted.');
    }
}
