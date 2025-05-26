<?php

namespace App\Http\Controllers\Admin;

use Amp\Parallel\Worker\Execution;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SubAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected const SUB_AGENT_ROlE = 8;

    public function index()
    {
        $users = User::with('roles')
            ->whereHas('roles', function ($query) {
                $query->where('role_id', self::SUB_AGENT_ROlE);
            })
            ->where('agent_id', auth()->id())
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.sub_acc.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $agent_name = $this->generateRandomString();

        return view('admin.sub_acc.create', compact('agent_name'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $agent = User::create([
                'user_name' => $request->user_name,
                'name' => $request->name,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'type' => UserType::subAgent,
                'agent_id' => Auth::id(),
            ]);
            $agent->roles()->sync(self::SUB_AGENT_ROlE);
        } catch (Exception $e) {
        }

        return redirect()->route('admin.subacc.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::find($id);

        return view('admin.sub_acc.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        return redirect()->route('admin.subacc.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function banSubAcc($id)
    {
        $user = User::find($id);
        $user->update(['status' => $user->status == 1 ? 0 : 1]);

        return redirect()->back()->with(
            'success',
            'User '.($user->status == 1 ? 'activate' : 'inactive').' successfully'
        );
    }

    public function getChangePassword($id)
    {
        $agent = User::find($id);

        return view('admin.sub_acc.change_password', compact('agent'));
    }

    public function makeChangePassword($id, Request $request)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $agent = User::find($id);
        $agent->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.subacc.index')
            ->with('successMessage', 'Agent Change Password successfully')
            ->with('password', $request->password)
            ->with('username', $agent->user_name);
    }

    private function generateRandomString()
    {
        $randomNumber = mt_rand(10000000, 99999999);

        return 'A'.$randomNumber;
    }
}
