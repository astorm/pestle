<?php
namespace Pulsestorm\Solonoble;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

function getPositionAndMapLegend() {
    $positionMovesToAndRemoves      = [];
    $positionMovesToAndRemoves[0]   = [NULL,NULL];
    $positionMovesToAndRemoves[1] = [4,2];
    $positionMovesToAndRemoves[1] = [6,3];
    $positionMovesToAndRemoves[2] = [7,4];
    $positionMovesToAndRemoves[2] = [9,5];
    $positionMovesToAndRemoves[3] = [8,5];
    $positionMovesToAndRemoves[3] = [10,6];
    $positionMovesToAndRemoves[4] = [1,2];
    $positionMovesToAndRemoves[4] = [11,7];
    $positionMovesToAndRemoves[5] = [8,12];
    $positionMovesToAndRemoves[5] = [9,13];
    $positionMovesToAndRemoves[6] = [9,12];
    $positionMovesToAndRemoves[6] = [10,15];
    $positionMovesToAndRemoves[7] = [2,4];
    $positionMovesToAndRemoves[8] = [3,5];
    $positionMovesToAndRemoves[9] = [5,2];
    $positionMovesToAndRemoves[10] = [6,3];
    $positionMovesToAndRemoves[11] = [4,7];
    $positionMovesToAndRemoves[12] = [8,5];
    $positionMovesToAndRemoves[13] = [8,4];
    $positionMovesToAndRemoves[14] = [9,5];
    $positionMovesToAndRemoves[15] = [10,6];
    return $positionMovesToAndRemoves;
}

function getBoardData()
{
    return [NULL,
              '@', '@', '@', '@', '@',
              '@', '@', '@', '@', '@',
              '@', '@', ' ', '@', '@'];
}

function renderBoard($data)
{
    return sprintf('
        [%s]
      [%s] [%s]
    [%s] [%s] [%s]
  [%s] [%s] [%s] [%s]
[%s] [%s] [%s] [%s] [%s]

',  $data[1], $data[2], $data[3], $data[4], $data[5],
    $data[6], $data[7], $data[8], $data[9], $data[10],
    $data[11], $data[12], $data[13], $data[14], $data[15]);
}
/**
* One Line Description
*
* @command pulsestorm:solo-noble
*/
function pestle_cli($argv)
{
    $data = getBoardData();
    echo renderBoard(getBoardData());
}
