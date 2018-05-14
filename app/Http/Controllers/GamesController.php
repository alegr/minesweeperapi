<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GamesController extends Controller
{
    /**
     * All possible directions to check for a certain cell in the grid.
     *
     * @var array
     */
    protected $directions = [
        [1,1],
        [1,0],
        [1,-1],
        [0,-1],
        [-1,-1],
        [-1,0],
        [-1,1],
        [0,1],
    ];

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

    /**
     * Create a new game.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function create(Request $request)
    {

        // Obtain request parameters
        $params = $request->all();

        // Validate parameters
        $validation = $this->validateNewMethod($params);
        if ($validation !== true) {
            return $validation;
        }

        // Create empty grid
        $grid = $this->createGrid($params['rows'], $params['columns']);

        // Set mines in grid
        $this->setMines($params['mines'], $grid);

        // Set numbers in grid
        $this->setNumbers($grid);

        // Create game in database
        $newGame = \App\Models\Game::create([
            'columns'       => $params['columns'],
            'rows'          => $params['rows'],
            'mines'         => $params['mines'],
            'user_id'       => isset($params['user_id'])? $params['user_id'] : 0,
            'free_spaces'   => ($params['rows']*$params['columns'])-$params['mines'],
        ]);

        // Store grid in file to simplify access and optimize performance
        $this->setGrid($newGame->id, $grid);

        return response()->json([
            'success' => true,
            'data' => [
                'game' => \App\Models\Game::find($newGame->id),
                'grid' => $grid,
            ],
        ]);
    }

    /**
     * Validate parameters for new method
     *
     * @param  array  $params
     * @return mixed
     */
    private function validateNewMethod($params)
    {
        $required = ['columns', 'rows', 'mines'];
        foreach ($required as $param) {
            if (!isset($params[$param])) {
                return $this->error(100, 'Parameter '.$param.' is mandatory.');
            }
        }

        // Mines quantity can't exceed amount of cells in grid
        if ( ($params['columns']*$params['rows']) < $params['mines']) {
            return $this->error(101, 'Total mines ('.$params['mines'].') exceed total cells for requested grid');
        }

        // Total cells must be a positive number
        if ($params['columns']*$params['rows'] < 0) {
            return $this->error(102, 'Grid must contain a positive amount of cells');
        }

        // Mines must be a positive number
        if ($params['mines'] < 0) {
            return $this->error(103, 'Mines must be positive number');
        }

        return true;
    }

    /**
     * Create new empty grid
     *
     * @param  int  $rows
     * @param  int  $columns
     * @return array
     */
    private function createGrid($rows, $columns)
    {
        $grid = [];
        for($y = 0; $y < $rows; $y++) {
            for ($x = 0; $x < $columns; $x++) {
                $grid[$y][$x] = ['value' => '', 'clicked' => false, 'display' => ''];
            }
        }

        return $grid;
    }

    /**
     * Set mines in a grid.
     *
     * @param  int  $mines
     * @param  array  $grid
     * @return void
     */
    private function setMines($mines, &$grid)
    {
        // Create array containing all possible coordinates
        $coordinates = [];
        for ($y = 0; $y < count($grid); $y++) {
            for ($x = 0; $x < count($grid[$y]); $x++) {
                $coordinates[] = [$y, $x];
            }
        }

        // Shuffle array and take {$mines} first locations
        shuffle($coordinates);
        array_splice($coordinates, $mines);

        // Mark those locations as mines
        foreach ($coordinates as $cell) {
            $grid[$cell[0]][$cell[1]]['value'] = 'M';
        }
    }

    /**
     * Set number of adjacent mines for a cell in a grid.
     *
     * @param  array  $grid
     * @return void
     */
    private function setNumbers(&$grid)
    {
        // Iterate each grid cell
        for ($y = 0; $y < count($grid); $y++) {
            for ($x = 0; $x < count($grid[$y]); $x++) {

                // Skip if mine
                if ($grid[$y][$x]['value'] == 'M') {
                    continue;
                }

                // Count adjacent mines
                $mines = 0;
                foreach ($this->directions as $dir) {
                    if (isset($grid[$y+$dir[0]]) && isset($grid[$y+$dir[0]][$x+$dir[1]])) {
                        if ($grid[$y+$dir[0]][$x+$dir[1]]['value'] == 'M') {
                            ++$mines;
                        }                        
                    }
                }

                // If mines were found, set number of mines. Otherwise set it as space. 
                $grid[$y][$x]['value'] = ($mines > 0)? $mines : '_';
            }
        }
    }

    /**
     * Return information for a particular game with its grid.
     *
     * @param int $id
     * @return object
     */
    public function show($id)
    {
        // Retrieve game model
        $game = \App\Models\Game::find($id);

        // Game not found
        if (!$game) {
            return $this->error(104, 'Game not found', 404);
        }

        // Retrieve game grid
        $grid = $this->getGrid($game->id);

        return response()->json([
            'success' => true,
            'data' => [
                'game' => $game,
                'grid' => $grid,
            ],
        ]);
    }

    /**
     * Set a cell as flag or question mark
     *
     * @param int $id
     * @return array
     */
    public function setDisplay($id, $x, $y, Request $request)
    {
        // Valid displays
        $displays = ['flag' => 'F', 'questionmark' => 'Q'];

        // Get requested display
        $segments = $request->segments();
        $display = $displays[$segments[count($segments)-2]];

        // Retrieve game model
        $game = \App\Models\Game::find($id);

        // Game not found
        if (!$game) {
            return $this->error(104, 'Game not found', 404);
        }

        // Retrieve game grid
        $grid = $this->getGrid($id);

        if (!isset($grid[$y]) || !isset($grid[$y][$x])) {
            return $this->error(107, 'Coordinates out of boundaries');
        }

        if ($grid[$y][$x]['clicked']) {
            return $this->error(107, 'Cell already clicked');
        }

        $grid[$y][$x]['display'] = $display;

        $this->setGrid($id, $grid);

        return response()->json([
            'success' => true,
            'data' => [
                'game' => $game,
                'grid' => $grid,
            ],
        ]);
    }

    /**
     * Click on a grid cell.
     *
     * @param  int  $game_id
     * @param  int  $x
     * @param  int  $y
     * @return array
     */
    public function click($game_id, $x, $y) 
    {
        // Retrieve grid from database
        $game = \App\Models\Game::find($game_id);

        // Game not found
        if (!$game) {
            return $this->error(104, 'Game not found', 404);
        }

        // Game is not active anymore
        if ($game->status != 'active') {
            return $this->error(105, 'Game is not active anymore. You already '.$game->status.' this game.');
        }

        // Retrieve grid
        $grid = $this->getGrid($game->id);

        if (!isset($grid[$y]) || !isset($grid[$y][$x])) {
            return $this->error(107, 'Coordinates out of boundaries');
        }

        // Check if cell is already clicked
        if ($grid[$y][$x]['clicked'] == true) {
            return $this->error(106, 'Cell is already clicked');
        }

        // Mine clicked
        if ($grid[$y][$x]['value'] == 'M') {

            // Update grid json
            $grid[$y][$x]['clicked'] = true;
            $grid[$y][$x]['display'] = 'M';
            $this->setGrid($game_id, $grid);

            $this->finishGame($game_id, 'lost');

            // Update grid json
            $this->setGrid($game_id, $grid);

            return response()->json([
                'success' => true, 
                'data' => [
                    'game' => \App\Models\Game::find($game_id), // Return updated game
                    'grid' => $grid,
                ]
            ]);
        }

        // Remove any display this cell might have
        $grid[$y][$x]['display'] = '';

        // Call internal click method
        $clicked = $this->_click($x, $y, $grid);

        // Update free spaces
        $game->free_spaces -= $clicked;
        $game->save();

        // Check if game has ended
        if ($game->free_spaces == 0) {
            $this->finishGame($game_id, 'won');
            // Return updated game
            $game = \App\Models\Game::find($game_id);
        }

        // Update grid json
        $this->setGrid($game_id, $grid);

        return response()->json([
            'success' => true, 
            'data' => [
                'game' => $game,
                'grid' => $grid,
            ]
        ]);
    }

    /**
     * Clicks on a cell and for space cells recursively clicks on adjacent ones 
     * from a starting point until there are no more free cells.
     *
     * @param  int  $x
     * @param  int  $y
     * @param  array  $grid
     * @return void
     */
    private function _click ($x, $y, &$grid, $clicked=0) {

        // Out of boundaries
        if (!isset($grid[$y]) || !isset($grid[$y][$x])) {
            return;
        }

        // Already clicked
        if ($grid[$y][$x]['clicked']) {
            return;
        }

        // Flagged or set as question mark, skip
        if (in_array($grid[$y][$x]['display'], ['F','Q'])) {
            return;
        }

        // Set cell as clicked
        $grid[$y][$x]['clicked'] = true;
        $grid[$y][$x]['display'] = $grid[$y][$x]['value'];

        // Increment clicked cells
        ++$clicked;

        // For spaces, call this method again for all possible directions
        if ($grid[$y][$x]['value'] == '_') {
            foreach ($this->directions as $direction) {
                $this->_click($x+$direction[0], $y+$direction[1], $grid, $clicked);
            }            
        }

        return $clicked;
    }

    /**
     * Completes a game.
     *
     * @param  int  $game_id
     * @param  string  $status
     * @return void
     */
    private function finishGame($game_id, $status) 
    {
        // Update game
        \App\Models\Game::find($game_id)->update([
            'status'        => $status,
            'endtime'       => date("Y-m-d H:i:s"),
        ]);
    }

    /**
     * Return grid for requested game.
     *
     * @param  int  $id
     * @return array
     */
    private function getGrid($id) 
    {
        return json_decode(Storage::disk('local')->get($id.'.json'), true);
    }

    /**
     * Store grid for requested game
     *
     * @param  int  $id
     * @param  mixed  $grid
     * @return array
     */
    private function setGrid($id, $grid) 
    {
        Storage::disk('local')->put($id.'.json', json_encode($grid));
    }

    /**
     * Return error object.
     *
     * @param  int  $code Internal error code
     * @param  string  $message
     * @param  int  $httpCode HTTP error code. 
     * @return Illuminate\Http\Response
     */
    private function error($code, $message, $httpCode=400) 
    {
        return response()->json([
            'success' => false,
            'error' => [ 
                'code' => $code, 
                'message' => $message,
            ],
        ])->setStatusCode($httpCode);
    }

}
