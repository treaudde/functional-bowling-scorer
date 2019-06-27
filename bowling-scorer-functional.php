<?php
//assumptions, there can be a maximum of (9x2)+ 3 (21) balls thrown in a game
//each strike reduces that amount by 1 for frames 1-9 so max balls in perfect game is 21-9 or 12 balls

function createNewGame()
{
    return [];
}

function checkForCallable($array) {
    foreach($array as $element) {
        if(is_callable($element)) {
            return true;
        }
    }

    return false;
}

$strikeScorer = function($ball2) {
    return function ($ball3) use ($ball2){
        return 10 + $ball2 + $ball3;
    };
};

$spareScorer = function($ball2){
    return 10 + $ball2;
};

$gameScorer = function ($currentGame, $roll) {
    return array_map(function ($frame) use ($roll) {

        if(is_callable($frame)) {
            return $frame($roll);
        }
        return $frame;

    }, $currentGame);

};

$rollBall = function ($currentGame, $gameScorer) use ($strikeScorer, $spareScorer) {
    $roll = rand(0,10);
    //$roll = 8;
    $currentGame = $gameScorer($currentGame, $roll);

    if($roll == 10) { //strike
        $currentGame[] = $strikeScorer;
        return $currentGame;
    }

    return function ($currentGame, $gameScorer) use ($roll, $spareScorer)  {
        $pinsLeft = 10 - $roll;
        //$nextRoll = rand(0, $pinsLeft);
        $nextRoll = 2;

        $currentGame = $gameScorer($currentGame, $roll);

        if($nextRoll == $pinsLeft) { //spare
            $currentGame[] = $spareScorer;
            return $currentGame;
        }
        //open frame
        $currentGame[] = $roll + $nextRoll;
        return $currentGame;
    };
};

$bowlingGame = createNewGame();
$x = 21; //max balls
do {
    $rollInProgress = $rollBall($bowlingGame, $gameScorer);

    if(is_array($rollInProgress)) {//we rolled a strike
        $bowlingGame = $rollInProgress;
        if(count($bowlingGame) <= 9) {// in the first 9 frames a strike means on
            $x -= 2;
        }
        else {//on the 10th frame a
            $x -= 1;
        }
    }

    if(is_callable($rollInProgress)) {//roll again
        $bowlingGame = $rollInProgress($bowlingGame, $gameScorer);
        $x -= 2;

        if(count($bowlingGame) == 10 && !checkForCallable($bowlingGame)) {//they opened the last frame
            $x = 0;
        }
    }
}
while ($x > 0);

echo "\nFinal Score: " . array_sum($bowlingGame)."\n";
