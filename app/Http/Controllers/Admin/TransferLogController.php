<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransferLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TransferLogController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $relatedIds = $this->getDirectlyRelatedUserIds($user);

        $query = TransferLog::with(['fromUser', 'toUser'])
            ->where(function ($q) use ($user, $relatedIds) {
                $q->where(function ($q2) use ($user, $relatedIds) {
                    $q2->where('from_user_id', $user->id)
                        ->whereIn('to_user_id', $relatedIds);
                })
                    ->orWhere(function ($q2) use ($user, $relatedIds) {
                        $q2->where('to_user_id', $user->id)
                            ->whereIn('from_user_id', $relatedIds);
                    });
            });

        // Apply filters if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        $transferLogs = $query->latest()->paginate(20);

        return view('admin.transfer_logs.index', compact('transferLogs'));
    }

    /**
     * Get only directly related user IDs according to the hierarchy:
     * Owner → Agent → SubAgent, Agent→Player, SubAgent→Player
     */
    private function getDirectlyRelatedUserIds(User $user): array
    {
        $relatedIds = [];
        if ($user->hasRole('Owner')) {
            // Owner: direct agents
            $relatedIds = $user->children()->whereHas('roles', function ($q) {
                $q->where('title', 'Agent');
            })->pluck('id')->toArray();
        } elseif ($user->hasRole('Agent')) {
            // Agent: direct players, direct subagents, parent owner
            $playerIds = $user->children()->whereHas('roles', function ($q) {
                $q->where('title', 'Player');
            })->pluck('id')->toArray();
            $subAgentIds = $user->children()->whereHas('roles', function ($q) {
                $q->where('title', 'SubAgent');
            })->pluck('id')->toArray();
            $parentOwnerId = $user->agent_id ? [$user->agent_id] : [];
            $relatedIds = array_merge($playerIds, $subAgentIds, $parentOwnerId);
        } elseif ($user->hasRole('SubAgent')) {
            // SubAgent: direct players, parent agent
            $playerIds = $user->children()->whereHas('roles', function ($q) {
                $q->where('title', 'Player');
            })->pluck('id')->toArray();
            $parentAgentId = $user->agent_id ? [$user->agent_id] : [];
            $relatedIds = array_merge($playerIds, $parentAgentId);
        }

        return array_unique($relatedIds);
    }

    public function transferLog($id)
{
    // Load the transfer log with both fromUser and toUser relationships
    $transferLog = \App\Models\TransferLog::with(['fromUser', 'toUser'])->findOrFail($id);

    // Optional: You can add authorization if needed (to restrict view access)
    // $this->authorize('view', $transferLog);

    return view('admin.player.log_detail', compact('transferLog'));
}

}
