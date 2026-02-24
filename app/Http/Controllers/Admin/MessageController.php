<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index($account, Request $request)
    {
        $tenant = app('tenant');
        $query  = Message::query();

        if ($request->type)    $query->where('source', $request->type);
        if ($request->status)  $query->where('status', $request->status);
        if ($request->starred) $query->where('is_starred', true);
        if ($request->search)  $query->where(function ($q) use ($request) {
            $q->where('sender_name',  'like', '%' . $request->search . '%')
              ->orWhere('sender_email', 'like', '%' . $request->search . '%')
              ->orWhere('message',      'like', '%' . $request->search . '%');
        });

        $messages    = $query->latest()->paginate(25)->withQueryString();
        $message     = null;
        if ($request->view) {
            $message = Message::where('tenant_id', $tenant->id)->findOrFail($request->view);
            if (!$message->is_read) $message->update(['is_read' => true]);
        }
        $unreadCount = Message::where('is_read', false)->count();

        return view('tenant.admin.messages.index', compact('tenant', 'messages', 'message', 'unreadCount'));
    }

    public function action($account, Request $request)
    {
        $tenant = app('tenant');
        $msg    = Message::where('tenant_id', $tenant->id)->findOrFail($request->id);
        match ($request->action) {
            'star'   => $msg->update(['is_starred' => !$msg->is_starred]),
            'read'   => $msg->update(['is_read' => true]),
            'unread' => $msg->update(['is_read' => false]),
            'status' => $msg->update(['status' => $request->status]),
            'delete' => $msg->delete(),
            default  => null,
        };
        return response()->json(['success' => true]);
    }

    public function bulk($account, Request $request)
    {
        $tenant = app('tenant');
        $ids    = array_map('intval', $request->ids ?? []);
        if (empty($ids)) return redirect()->back();

        match ($request->action) {
            'read'   => Message::where('tenant_id', $tenant->id)->whereIn('id', $ids)->update(['is_read' => true]),
            'delete' => Message::where('tenant_id', $tenant->id)->whereIn('id', $ids)->delete(),
            default  => null,
        };
        return redirect()->back()->with('success', 'Bulk action done.');
    }
}
