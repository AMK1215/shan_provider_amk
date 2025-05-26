<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Enums\UserType;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class AdminLogoMiddleware
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $logoFilename = null;
            $siteName = null;

            try {
                $current = $user;
                // Traverse up the user tree until Owner or no parent
                while ($current) {
                    if ($current->agent_logo && !$logoFilename) {
                        $logoFilename = $current->agent_logo;
                    }
                    if ($current->site_name && !$siteName) {
                        $siteName = $current->site_name;
                    }
                    // Stop if Owner
                    if (isset($current->user_type) && $current->user_type == UserType::Owner->value) {
                        break;
                    }
                    // Go up the tree
                    if (isset($current->agent_id) && $current->agent_id) {
                        $current = User::find($current->agent_id);
                    } else {
                        break;
                    }
                }
                // Fallback to user's own if still not found
                if (!$logoFilename) {
                    $logoFilename = $user->agent_logo;
                }
                if (!$siteName) {
                    $siteName = $user->site_name;
                }
                $adminLogo = $logoFilename
                    ? asset('assets/img/logo/'.$logoFilename)
                    : asset('assets/img/logo/default-logo.png');
                View::share([
                    'adminLogo' => $adminLogo,
                    'siteName' => $siteName ?? "GSCPLUSSlotGameSite",
                ]);
            } catch (\Exception $e) {
                Log::error('Error in AdminLogoMiddleware: ' . $e->getMessage());
                // Fallback to default values
                View::share([
                    'adminLogo' => asset('assets/img/logo/default-logo.png'),
                    'siteName' => "GSCPLUSSlotGameSite",
                ]);
            }
        }
        return $next($request);
    }

    // public function handle($request, Closure $next)
    // {
    //     if (Auth::check()) {
    //          $logoFilename = Auth::user()->agent_logo;
    // Log::info('Auth User Logo:', ['logo' => $logoFilename]);
    //         $adminLogo = Auth::user()->agent_logo ? asset('assets/img/logo/' . Auth::user()->agent_logo) : asset('assets/img/logo/default-logo.jpg');
    // Log::info('Admin Logo Path:', ['path' => $adminLogo]);
    //         View::share('adminLogo', $adminLogo);
    //     }

    //     return $next($request);
    // }
}
