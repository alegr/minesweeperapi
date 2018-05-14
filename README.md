
# minesweeper-API

API for building a minesweeper application. 

## Installation

### Clone repository

    git clone https://github.com/alegr/minesweeperapi.git 

### Install packages from composer

    composer install

### Configure

Create .env configuration file from the .env.example file with local env information including local MySQL database

### Create tables

Run migrations to create database tables

    php artisan migrate

If you are getting 'Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes' error when running migrations, then you need to add this fix https://laravel-news.com/laravel-5-4-key-too-long-error

### Apache configuration

Remember to point your document root to the `public` folder of your application. Your API will be working starting with the /api namespace. If your local domain is http:://minesweeper.app, then you'll be able to start making requests to http:://minesweeper.app/api (http:://minesweeper.app/api/games for instance).

## Response structure

All API responses will be wrapped in the following structure

### Succesful responses
    {
        success: true,
        data: {} // Response content
    }

### Unsuccesful responses
    {
        success: false, // A validation or requirement was not met.
        error: {
            code: 101, // Internal error code. Not to be mistaken with HTTP code.
            message: "This is the error message" // Error description
        }
    }

## Objects

### Game model

    game: {
        id: 1, // Game id
        ctime: "YYYY-MM-DD HH:MM:SS", // Created timestamp
        etime: "YYYY-MM-DD HH:MM:SS" // Elapsed timestamp
        columns: 20, // Total columns
        rows: 20, // Total rows
        mines: 20, // Total mines
        user_id: 0, // User id
        endtime: "YYYY-MM-DD HH:MM:SS" // End game timestamp
        status: 'active',
        free_spaces: 14            
    },

### Grid collection

The grid is returned in a rows x columns format for easy access to each position. Each cell shows its real value (`value`), if it has been clicked (`clicked`) and its display value (`display`). 

Values could be any of the following: 

- _: Space. No mines are adjacent to this cell.
- [1-8]: Any number from 1 to 8 showing how many mines the current cell is adjacent to.
- M: The cell contains a mine.

Display values could be any of the following: 
- F: Flag
- Q: Question mark
- _: Space. No mines are adjacent to this cell.
- [1-8]: Any number from 1 to 8 showing how many mines the current cell is adjacent to.

The following example is from a 3x3 grid:

    grid: [
        [
            { value: 'M', clicked: true, display: 'F' },
            { value: '1', clicked: false, display: '' },
            { value: '_', clicked: false, display: '' }
        ],
        [
            { value: 'M', clicked: true, display: 'Q' },
            { value: '2', clicked: false, display: '' },
            { value: '_', clicked: false, display: '' }
        ],
        [
            { value: '_', clicked: true, display: '_' },
            { value: '_', clicked: true, display: '_' },
            { value: '1', clicked: true, display: '1' }
        ]
    ]

## Available methods

### GET /games

Retrieve a list of all created games. All 'pending' games can be resumed. Returns a collection of game objects

### POST /games

Create a new game. Receive parameters for rows, columns and mines to create grid. Returns new game model.

user_id parameter is opcional, but it can be modified later on to support user sessions and to take it directly from the logged user.

#### Parameters
    {
        columns: 50, // Mandatory
        rows: 50, // Mandatory
        mines: 12, // Mandatory
        user_id: 1 // Optional
    }

#### Response
    {
        success: true,
        data: {
            game: {} // See game model
            grid: [] // See grid collection
        }
    }

### GET /game/{id}

Retrieve full grid for requested game id. 

#### Response
    {
        success: true,
        data: {
            game: {} // See game model
            grid: [] // See grid collection
        }
    }

### POST /game/{id}/click/{x-y}

Click the coordinates (x,y) on the {id} game. 

#### Response
    {
        success: true,
        data: {
            game: {} // See game model
            grid: [] // See grid collection
        }
    }

### POST /game/{id}/flag/{x-y}

Set the coordinate (x,y) with a flag. This represents that the coordinate is presumed to be a mine.

#### Response
    {
        success: true,
        data: {
            game: {} // See game model
            grid: [] // See grid collection
        }
    }

### POST /game/{id}/questionmark/{x-y}

Set the coordinate (x,y) with a question-mark. This represents that the coordinate could be a mine.

#### Response
    {
        success: true,
        data: {
            game: {} // See game model
            grid: [] // See grid collection
        }
    }


