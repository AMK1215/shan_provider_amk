<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdsVedio;
use App\Models\Admin\Banner;
use App\Models\Admin\BannerAds;
use App\Models\Admin\BannerText;
use App\Models\Admin\TopTenWithdraw;
use App\Models\WinnerText;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;

class BannerController extends Controller
{
    use HttpResponses;

    public function index()
    {
        $user = Auth::user();

        $admin = $user->parent;

        $data = Banner::where('admin_id', $admin->agent_id)->get();

        return $this->success($data, 'Banners retrieved successfully.');
    }

    public function TopTen()
    {
        $user = Auth::user();
        $admin = $user->parent;

        $data = TopTenWithdraw::where('admin_id', $admin->agent_id)->get();

        return $this->success($data, 'TopTen Winner retrieved successfully.');
    }

    public function bannerText()
    {
        $user = Auth::user();

        $admin = $user->parent;

        $data = BannerText::where('admin_id', $admin->agent_id)->get();

        return $this->success($data, 'BannerTexts retrieved successfully.');
    }

    public function AdsBannerIndex()
    {
        $user = Auth::user();

        $admin = $user->parent;

        $data = BannerAds::where('admin_id', $admin->agent_id)->get();

        return $this->success($data, 'BannerAds retrieved successfully.');
    }

    public function winnerText()
    {
        $user = Auth::user();

        $admin = $user->parent;

        $data = WinnerText::where('owner_id', $admin->agent_id)->latest()->first();

        return $this->success($data, 'Winner Text retrieved successfully.');

    }

    public function ApiVideoads()
    {
        $user = Auth::user();

        // Determine the admin whose banners to fetch
        if ($user->parent) {
            // If the user has a parent (Agent or Player), go up the hierarchy
            // $admin = $user->parent->parent ?? $user->parent;
            $admin = $user->parent;

        } else {
            // If the user is an Admin, they own the banners
            $admin = $user;
        }

        // Fetch banners for the determined admin
        $data = AdsVedio::where('admin_id', $admin->id)->get();

        return $this->success($data, 'AdsVedio retrieved successfully.');
    }
}
