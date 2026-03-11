<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FeedbackController extends Controller
{
    public function create($account)
    {
        $tenant = app('tenant');
        return view('tenant.admin.feedback', compact('tenant'));
    }

    public function store($account, Request $request)
    {
        $request->validate([
            'subject'        => 'required|string|max:200',
            'message'        => 'required|string|max:5000',
            'screenshots'    => 'nullable|array|max:10',
            'screenshots.*'  => 'image|mimes:jpeg,png,gif,webp|max:8192',
        ]);

        $tenant = app('tenant');
        $paths  = [];

        if ($request->hasFile('screenshots')) {
            $dir = 'feedback/' . $tenant->id;
            foreach ($request->file('screenshots') as $file) {
                $name   = uniqid() . '.' . $file->getClientOriginalExtension();
                $paths[] = Storage::disk('local')->putFileAs($dir, $file, $name);
            }
        }

        Feedback::create([
            'tenant_id'       => $tenant->id,
            'user_id'         => auth()->id(),
            'subject'         => $request->subject,
            'message'         => $request->message,
            'screenshot_path' => $paths,
        ]);

        return redirect()->route('tenant.admin.feedback.thanks', $account);
    }

    public function thanks($account)
    {
        $tenant = app('tenant');
        return view('tenant.admin.feedback-thanks', compact('tenant'));
    }
}
