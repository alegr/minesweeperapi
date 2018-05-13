<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GamesController extends Controller
{

    /**
     * Return a list of all games.
     *
     * @return array
     */
    public function index(Request $request)
    {
        $games = \App\Models\Game::all();
        return response()->json([
            'success' => true,
            'data' => $games,
        ]);
    }

}
