<?php

namespace App\Http\Controllers;

use App\Models\UserEvent;
use Illuminate\Http\Request;

class UserEventController extends Controller
{
    public function index()
    {
        return response()->json(UserEvent::with(['eventType', 'vehicle', 'userToNotify'])->get());
    }

    public function show(UserEvent $userEvent)
    {
        return response()->json($userEvent->load(['eventType', 'vehicle', 'userToNotify']));
    }

    public function destroy(UserEvent $userEvent)
    {
        $userEvent->delete();
        return response()->json(['message' => 'Event deleted successfully']);
    }
}
