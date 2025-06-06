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
        // if ($request->has('date_from') && $request->has('date_to')) {
        //     $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        // }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $from = $request->date_from . ' 00:00:00';
            $to = $request->date_to . ' 23:59:59';
            $query->whereBetween('created_at', [$from, $to]);
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

    public function PlayertransferLog($relatedUserId)
    {
        $user = Auth::user();
        $relatedUser = \App\Models\User::findOrFail($relatedUserId);

        // (Optionally) Only allow if related user is a child or parent, or else abort
        // ...classic check here...

        // Show all logs just between $user and $relatedUser
        $transferLogs = \App\Models\TransferLog::with(['fromUser', 'toUser'])
            ->where(function ($q) use ($user, $relatedUser) {
                $q->where('from_user_id', $user->id)
                    ->where('to_user_id', $relatedUser->id);
            })
            ->orWhere(function ($q) use ($user, $relatedUser) {
                $q->where('from_user_id', $relatedUser->id)
                    ->where('to_user_id', $user->id);
            })
            ->latest()
            ->get();

        return view('admin.transfer_logs.player_transfer_log_index', compact('transferLogs', 'relatedUser'));
    }

    //     public function PlayertransferLog($id)
    // {
    //     $user = Auth::user();

    //     // 1. Find parent agent if current user is a subagent
    //     $agent = $user;
    //     if ($user->hasRole('SubAgent') && $user->agent_id) {
    //         $agent = \App\Models\User::find($user->agent_id);
    //     }

    //     // 2. Gather allowed IDs (subagent and parent agent)
    //     $allowedIds = [$user->id];
    //     if ($agent && $agent->id !== $user->id) {
    //         $allowedIds[] = $agent->id;
    //     }

    //     // 3. Fetch all transfer logs involving these IDs
    //     $transferLogs = \App\Models\TransferLog::with(['fromUser', 'toUser'])
    //         ->where(function ($q) use ($allowedIds) {
    //             $q->whereIn('from_user_id', $allowedIds)
    //               ->orWhereIn('to_user_id', $allowedIds);
    //         })
    //         ->latest()
    //         ->get();

    //     // 4. (Optional) If you want to highlight a specific log, you can still fetch it:
    //     $transferLog = $transferLogs->where('id', $id)->first();
    //     if (!$transferLog) {
    //         abort(404, 'Transfer log not found or not accessible.');
    //     }

    //     return view('admin.transfer_logs.player_transfer_log_index', compact('transferLog', 'transferLogs'));
    // }

    //     public function PlayertransferLog($id)
    // {
    //     $user = Auth::user();

    //     // 1. Identify the top-level agent (could be themselves or their parent if subagent)
    //     $agent = $user;
    //     if ($user->hasRole('SubAgent') && $user->agent_id) {
    //         $agent = \App\Models\User::find($user->agent_id);
    //     }

    //     // 2. Collect all IDs allowed to view this log: the subagent, their agent, and optionally owner
    //     $allowedIds = [$user->id];
    //     if ($agent && $agent->id !== $user->id) {
    //         $allowedIds[] = $agent->id;
    //     }

    //     // (Optional) If owner should always see, add owner id, e.g.:
    //     // if ($user->hasRole('Owner')) { $allowedIds[] = $user->id; }

    //     // 3. Fetch the log with eager relationships
    //     $transferLog = \App\Models\TransferLog::with(['fromUser', 'toUser'])->findOrFail($id);

    //     // 4. Allow access if this log involves either the subagent or their agent
    //     if (
    //         !in_array($transferLog->from_user_id, $allowedIds)
    //         && !in_array($transferLog->to_user_id, $allowedIds)
    //     ) {
    //         abort(403, 'You do not have access to this transfer log.');
    //     }

    //     return view('admin.player.log_detail', compact('transferLog'));
    // }

}
