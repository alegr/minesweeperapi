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
