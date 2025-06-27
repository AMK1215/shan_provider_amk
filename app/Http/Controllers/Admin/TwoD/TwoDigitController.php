<?php

namespace App\Http\Controllers\Admin\TwoD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TwoDigit\HeadClose;
use App\Models\TwoDigit\ChooseDigit;

class TwoDigitController extends Controller
{
    // head close digit
    public function headCloseDigit()
    {
        // get all head close digit
        $headCloseDigits = HeadClose::all();
        // get all choose close digit
        $chooseCloseDigits = ChooseDigit::all();
        return view('admin.two_digit.close_digit.index', compact('headCloseDigits', 'chooseCloseDigits'));
    }

    // choose close digit
    public function chooseCloseDigit()
    {
        $chooseCloseDigits = ChooseDigit::all();
        return view('admin.two_digit.close_digit.index', compact('chooseCloseDigits'));
    }

    // toggle status
    public function toggleStatus(Request $request)
    {
        $digit = HeadClose::find($request->id);
        $digit->status = $request->status;
        $digit->save();
        return response()->json(['success' => true]);
    }

    // toggle choose digit status
    public function toggleChooseDigitStatus(Request $request)
    {
        $digit = ChooseDigit::find($request->id);
        $digit->status = $request->status;
        $digit->save();
        return response()->json(['success' => true]);
    }

    
}
