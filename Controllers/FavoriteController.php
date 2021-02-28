<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\Request;

class GithubController extends Controller
{

    public function delete(Request $request, $id)
    {
        $id = Favorite::find($id);
        $id ->delete();

        return redirect()->back()->with('message', 'DELETED successfully!');
    }

    public function index(Request $request)
    {
        $favorite = Favorite::all();
        $id = Favorite::id();

        return view('favorites', ['favorite'=>$favorite, 'id'=>$id]);
    }
}