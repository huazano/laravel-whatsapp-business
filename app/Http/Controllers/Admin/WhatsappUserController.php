<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhatsappUserController extends Controller
{
    /**
     * Display a listing of WhatsApp users.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $users = WhatsappUser::query()
            ->with(['conversations' => function ($query) {
                $query->latest('last_message_at')->limit(1);
            }])
            ->withCount('conversations')
            ->when($search, function ($query, $search) {
                $query->where('phone_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            })
            ->latest('last_interaction_at')
            ->paginate(20)
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'phone_number' => $user->phone_number,
                    'name' => $user->name,
                    'profile_picture' => $user->profile_picture,
                    'is_active' => $user->is_active,
                    'last_interaction_at' => $user->last_interaction_at?->diffForHumans(),
                    'conversations_count' => $user->conversations_count,
                    'roles' => $user->getRoleNames(),
                    'last_conversation' => $user->conversations->first() ? [
                        'id' => $user->conversations->first()->id,
                        'status' => $user->conversations->first()->status,
                        'last_message_at' => $user->conversations->first()->last_message_at?->diffForHumans(),
                    ] : null,
                ];
            });

        return Inertia::render('admin/whatsapp-users/index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Display the specified WhatsApp user with their conversation.
     */
    public function show(WhatsappUser $whatsappUser): Response
    {
        $whatsappUser->load([
            'conversations' => function ($query) {
                $query->active()->latest('last_message_at');
            },
        ]);

        $activeConversation = $whatsappUser->conversations->first();

        return Inertia::render('admin/whatsapp-users/show', [
            'user' => [
                'id' => $whatsappUser->id,
                'phone_number' => $whatsappUser->phone_number,
                'name' => $whatsappUser->name,
                'profile_picture' => $whatsappUser->profile_picture,
                'is_active' => $whatsappUser->is_active,
                'last_interaction_at' => $whatsappUser->last_interaction_at,
                'created_at' => $whatsappUser->created_at,
                'roles' => $whatsappUser->getRoleNames(),
                'permissions' => $whatsappUser->getAllPermissions()->pluck('name'),
            ],
            'conversation' => $activeConversation ? [
                'id' => $activeConversation->id,
                'status' => $activeConversation->status,
                'last_message_at' => $activeConversation->last_message_at,
            ] : null,
        ]);
    }

    /**
     * Update the specified WhatsApp user's role.
     */
    public function updateRole(Request $request, WhatsappUser $whatsappUser)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $whatsappUser->syncRoles([$request->role]);

        return back()->with('success', 'User role updated successfully');
    }

    /**
     * Toggle user active status.
     */
    public function toggleActive(WhatsappUser $whatsappUser)
    {
        $whatsappUser->update([
            'is_active' => !$whatsappUser->is_active,
        ]);

        return back()->with('success', 'User status updated successfully');
    }
}
