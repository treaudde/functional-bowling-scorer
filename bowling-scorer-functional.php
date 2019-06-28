<?php
//assumptions, there can be a maximum of (9x2)+ 3 (21) balls thrown in a game
//each strike reduces that amount by 1 for frames 1-9 so max balls in perfect game is 21-9 or 12 balls
define('STRIKE', 'X');
define('SPARE', '/');
define('MISS', '-');
define('PLUS_10', 10);
define('NUM_FRAMES', 10);
define('MIN_BALLS', 12);
define('MAX_BALLS', 21);

/**
 * Utility method to see if there are callable elements in an array
 * @param $array
 * @return bool
 */
function checkForCallable($array) {
    foreach($array as $element) {
        if(is_callable($element)) {
            return true;
        }
    }

    return false;
}


/**
 * Sample games for testing
 *
 * @param $gameType
 * @return array
 */
function sampleGames($gameType) {
    $games = [
        'perfectGame' => array_merge(array_fill('0', 9, STRIKE), [STRIKE." ".STRIKE. " ".STRIKE]),
        'zeroGame' => array_fill('0', 12, MISS),
        'dutch200' => [STRIKE, "9 ".SPARE, STRIKE, "9 ".SPARE, STRIKE, "9 ".SPARE, STRIKE, "9 ".SPARE, STRIKE, "9 ".SPARE, STRIKE, "9 ".SPARE." ".STRIKE],
        '299game' => array_merge(array_fill('0', 9, STRIKE), [STRIKE." ".STRIKE. " 9"]),
        '90game' => array_fill('0', 10, '9 '.MISS),
        '190game' => array_merge(array_fill('0', 9, '9 '.SPARE), ['9 '.SPARE.' 9']),
        '110game' => array_merge(array_fill('0', 9, '1 '.SPARE), ['1 '.SPARE.' 1']),
    ];

    return $games[$gameType];
}

/**
 * @param $game
 * @return array
 */
function translateGameToBalls($game) {
    $frames = array_map(function($frame){
        return explode(' ', $frame);

    }, $game);

    $balls = [];
    foreach($frames as $frame) {
        $balls = array_merge($balls, $frame);
    }

    return $balls;
}


/**
 * Scorer a strike ball
 *
 * @param $ball2
 * @return Closure
 */
$strikeScorer = function($ball2) {
    return function ($ball3) use ($ball2){
        return PLUS_10 + $ball2 + $ball3;
    };
};

/**
 * Scorer for a spare ball
 *
 * @param $ball2
 * @return int
 */
$spareScorer = function($ball2){
    return PLUS_10 + $ball2;
};

/**
 * This scores the current game in progress
 *
 * @param $currentGame
 * @param $roll
 * @return array
 */
$gameScorer = function ($currentGame, $roll) {
    return array_map(function ($frame) use ($roll) {

        if(is_callable($frame)) {
            return $frame($roll);
        }
        return $frame;

    }, $currentGame);

};

/**
 * Simulates ball roll, returns a second roller if not a strike
 * Return the scoreboard from the second roller
 *
 * @param $currentGameScoreBoard
 * @param $gameScorer
 * @param $roll
 *
 * @return array|Closure
 */
$rollBall = function ($currentGameScoreBoard, $gameScorer, $roll) use ($strikeScorer, $spareScorer) {
    $currentGameScoreBoard = $gameScorer($currentGameScoreBoard, ($roll == STRIKE) ? 10 : $roll);

    if($roll == STRIKE) { //strike
        $currentGameScoreBoard[] = $strikeScorer;
        return $currentGameScoreBoard;
    }

    return function ($currentGameScoreBoard, $gameScorer, $nextRoll) use ($roll, $spareScorer)  {
        $currentGameScoreBoard = $gameScorer($currentGameScoreBoard, ($roll == STRIKE) ? 10 : $roll);

        if($nextRoll == SPARE) { //spare
            $currentGameScoreBoard[] = $spareScorer;
            return $currentGameScoreBoard;
        }
        //open frame
        //translate  the miss character
        $nextRoll = ($nextRoll == MISS) ? 0 : $nextRoll;

        $currentGameScoreBoard[] = $roll + $nextRoll;
        return $currentGameScoreBoard;
    };
};


/**
 * Bootstrap function
 * TODO make this return a function so we can step through it
 *
 * @param $game
 * @param array $bowlingGameScoreBoard
 * @return array|Closure
 */
$playGame = function ($game, $bowlingGameScoreBoard = []) use ($gameScorer, $rollBall) {
    if (count($game) != NUM_FRAMES) {//make sure the number of frames are correct
        echo "\nInvalid Game. A game must be 10 frames exactly\n\n";
        exit;
    }

    $balls = translateGameToBalls($game); //translate the game to balls to be thrown

    if(count($balls) < MIN_BALLS || count($balls) > MAX_BALLS) {//make sure the number of balls makes sense
        echo "\nInvalid Game. A game must be between ".MIN_BALLS." and ".MAX_BALLS." balls\n\n";
        exit;
    }

    $rollInProgress = null; //initalize this value
    for($x=0; $x<count($balls); $x++) {
        //get a new roller or use the returned roller
        $rollInProgress = (is_callable($rollInProgress)) ?
            $rollInProgress($bowlingGameScoreBoard, $gameScorer, $balls[$x]) :
                $rollBall($bowlingGameScoreBoard, $gameScorer, $balls[$x]);

        //if we have a strike or a finished frame, set the scoreboard
        if(is_array($rollInProgress)) {
            $bowlingGameScoreBoard = $rollInProgress;
            $rollInProgress = null;
        }
    }

    return $bowlingGameScoreBoard;
};


echo "Play perfect game\n";
$scoreBoard = $playGame(sampleGames('perfectGame'), []);
echo "Final Score: " . array_sum($scoreBoard)."\n";
