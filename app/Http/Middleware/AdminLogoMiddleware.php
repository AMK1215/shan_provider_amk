<?php

namespace App\Http\Middleware;

use App\Models\User;
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

            if($user->hasRole('Owner'))  {
                $logoFilename = $user->agent_logo;
                $siteName  =  $user->site_name;
            } elseif ($user->hasRole('Senior')) {
                $ownerId = User::where('id',$user->agent_id)->first();
                $logoFilename = $ownerId->agent_logo;
                $siteName  = $ownerId->site_name;
            } elseif ($user->hasRole('Master')) {
                $seniorId = User::where('id',$user->agent_id)->first();
                $ownerId  = User::where('id', $seniorId->agent_id)->first();
                $logoFilename =  $ownerId->agent_logo;
                $siteName  =  $ownerId->site_name;
            } elseif ($user->hasRole('Agent')) {
                $masterId = User::where('id',$user->agent_id)->first();
                $seniorId = User::where('id',$masterId->agent_id)->first();
                $ownerId  = User::where('id', $seniorId->agent_id)->first();
                $logoFilename =  $ownerId->agent_logo;
                $siteName  =  $ownerId->site_name;
            } else {
                $logoFilename = $user->agent_logo;
                $siteName  =  $user->site_name;
            }

            //Log::info('Auth User Logo:', ['logo' => $logoFilename]);
            //Log::info('Site Name:', ['site_name' => $siteName]);

            $adminLogo = $logoFilename
                ? asset('assets/img/logo/'.$logoFilename)
                : asset('assets/img/logo/default-logo.png');

            //Log::info('Admin Logo Path:', ['path' => $adminLogo]);

            View::share([
                'adminLogo' => $adminLogo,
                'siteName' => $siteName ?? "PoneWine20x", // Share site name globally
            ]);
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
