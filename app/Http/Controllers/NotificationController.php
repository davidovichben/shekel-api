<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::where('business_id', current_business_id());

        if ($request->has('is_read')) {
            $query->where('is_read', filter_var($request->is_read, FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = $request->get('limit', 15);
        $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'rows' => $notifications->items(),
            'counts' => [
                'total_rows' => $notifications->total(),
                'total_pages' => $notifications->lastPage(),
            ],
        ]);
    }

    public function markRead(Notification $notification)
    {
        if ($notification->business_id !== current_business_id()) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json($notification);
    }

    public function markAllRead()
    {
        Notification::where('business_id', current_business_id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
