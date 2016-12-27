<?php
namespace Pulsestorm\Cli\Monty_Hall_Problem;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

function doorMontyReveals($winningDoor, $doorChosen)
{
    $doors        = [1=>1,2=>2,3=>3];
    unset($doors[$winningDoor]);
    unset($doors[$doorChosen]);
    return array_pop($doors);
}

function switchDoor($doorChosen, $montysDoor)
{
    if($doorChosen === $montysDoor)
    {
        exit("Error at " . __LINE__);
    }
    $doors        = [1=>1,2=>2,3=>3];
    unset($doors[$doorChosen]);
    unset($doors[$montysDoor]);
    return array_pop($doors);    
}

function vaidateStrategyAndShouldWeKeepOurDoor($strategy)
{
    $keepDoor = $strategy === 'keep_door'   ? true : false;
    if(!$keepDoor && $strategy !== 'change_door'){
        output("Unknown Strategy Chosen");
        exit;
    }
    return $keepDoor;
}

function runStrategy($keepDoor, $doorChosen, $montysDoor, $strategy)
{
    if($keepDoor)
    {
        output("You keep your door:             $doorChosen");
    }
    else
    {
        $doorChosen = switchDoor($doorChosen, $montysDoor);
        output("You changed to door:            $doorChosen");
    }
    return $doorChosen;
}

/**
 * Runs end game state
 * @return boolean true if we own, false if we lost
 */
function runEndGame($winningDoor, $doorChosen)
{
    output("The Winning Door:               $winningDoor");            
    //return true if won, false if lost
    if(($winningDoor === $doorChosen))
    {
        output("You Win!");
        return true;
    }
    output("You Lose!");
    return false;
}

function getStartingGameState()
{
    $start = [
        rand(1,3),  //'winningDoor'=>
        rand(1,3),  //'doorChosen' =>
    ];
    $start[] = doorMontyReveals(
        $start[0], $start[1]);
        
    return $start;        
}

function runGame($argv, $keepDoor)
{
    //game start
    list($winningDoor, $doorChosen, $montysDoor) = getStartingGameState();
    output("You have chosen door:           $doorChosen");
    output("Monty reveals the zonk door:   $montysDoor");
    
    //change or keep your door
    $doorChosen = runStrategy($keepDoor, $doorChosen, $montysDoor, $argv['strategy']);
    
    //run game end state, get won/loss
    $won = runEndGame($winningDoor, $doorChosen);
    output('');
    return $won;
}

function outputResults($results)
{
    output("Times Won:  " . $results['win']);
    output("Times Lost: " . $results['lose']);
}

function runSimulation($argv, $results, $keepDoor, $times)
{
    for($i=0;$i<$times;$i++)
    {
        $won    = runGame($argv, $keepDoor);
        if($won)
        {
            $results['win']++;
            continue;
        }
        $results['lose']++;
    }        
    return $results;
}

/**
* Runs Simulation of "Monty Hall Problem"
*
* You have three doors.  One has a prize behind it.  The other
* two have no prizes behind it.  You pick a door.  The game 
* show host, Monty Hall, shows you that one of the remaining 
* doors has no prize behind it.  
* 
* Should you switch doors?
*
* Assumes there's only one winning door, and that Monty will always
* reveal a zonk door.  Also, The **New** Lets Make a Deal from the 80s (the 
* one I'm familiar with would sometimes change this up with a 
* "medium prize" door.  Also assumes that the door picking is completely 
* random, and that show producers aren't using cold reading or "door forcing" 
* techniques on the contestants.  Also assumes the producers had no access
* to the contestant to tell them which doors to pick or to not pick.  
*
* @command monty_hall_problem
* @argument strategy Which Strategy (keep_door|change_door)? [keep_door]
* @argument times Run Game N Times [10000]
*/
function pestle_cli($argv)
{    
    $results = [
        'win'=>0,
        'lose'=>0,
    ];
    
    $keepDoor   = vaidateStrategyAndShouldWeKeepOurDoor($argv['strategy']);
    $times      = (int) $argv['times'];
    
    $results = runSimulation($argv, $results, $keepDoor, $times);
    outputResults($results);
}
