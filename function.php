<?php
session_start();
init();

$CURRENT_URL = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$CURRENT_URL = strtok($CURRENT_URL, "?");

function init()
{
	if (isset($_POST['start']) || !isset($_SESSION['status']))
	{
		game_start();
	}
	elseif(isset($_POST['resign']))
	{
		$_SESSION['status'] = 'resign';
		game_save();
		game_start();
	}

	else{
		game_turn();
	}
}


function game()
{
	global $CURRENT_URL;
	$el = null;
	if(isset($_GET['action']) && $_GET['action'] == 'setName')
	{
		$el .= game_setName();
	}
	elseif(isset($_GET['action']) && $_GET['action'] == 'showResult')
	{
		$el .= game_showResult();
	}
	else
	{
		$el .= "<form method='post' id='game' class='form'>";
		$el .= game_create();
		$el .= game_winner();
		$el .= game_resetBtn();
		$el .= "<br /><a href='$CURRENT_URL?action=showResult'>Show Result</a>";
		$el .= '</form>';
	}

	return $el;
}


function game_start()
{
	$_SESSION['status']      = 'start';
	$_SESSION['game']        =
	[
		1 => null,
		2 => null,
		3 => null,
		4 => null,
		5 => null,
		6 => null,
		7 => null,
		8 => null,
		9 => null,
	];

	if(!isset($_SESSION['playerNames']))
	{
		$_SESSION['playerNames'] = [ 'X' => 'player1', 'O' => 'player2'];
	}

	if(isset($_SESSION['lastWinner']))
	{
		$_SESSION['current'] = $_SESSION['lastWinner'];
	}
	else
	{
		$randPlayer          = array_rand($_SESSION['playerNames'], 1);
		$_SESSION['current'] = $randPlayer;
	}
	// save start time
	$_SESSION['game_time_start'] = time();
	$_SESSION['game_move_x'] = 0;
	$_SESSION['game_move_o'] = 0;
}


function game_activeChecker($_player)
{
	if($_SESSION['current'] !== $_player)
	{
		return ' active';
	}
	return null;
}

function game_create()
{
	$element = null;
	$element .= "<div class='row title'>";
	$element .= "<div class='span6". game_activeChecker('O'). "'>". $_SESSION['playerNames']['X'] ."(<span class='cX'>X</span>)</div>";
	$element .= "<div class='span6". game_activeChecker('X'). "'>". $_SESSION['playerNames']['O'] ."(<span class='cO'>O</span>)</div>";
	$element .=	"</div>";

	foreach ($_SESSION['game'] as $cell => $value)
	{
		$className = null;
		if($value)
		{
			$className = 'c'.$value;
		}
		$element .= "    <input type='submit' class='cell $className' value='$value' name='cell$cell'";
		if($value)
		{
			$element .= " disabled";
		}
		elseif($_SESSION['status'] == 'win' || $_SESSION['status'] == 'draw')
		{
			$element .= " disabled";
		}
		$element .=  ">\n";
	}
	return $element;
}


function game_turn()
{
	if($_SESSION['status'] == 'win' || $_SESSION['status'] == 'draw')
	{
		return null;
	}

	foreach ($_SESSION['game'] as $cell => $value)
	{
		if (isset($_POST['cell'.$cell]))
		{
			$_SESSION['status'] = 'inprogress';
			if($_SESSION['game'][$cell] === null)
			{
				$_SESSION['game'][$cell] = $_SESSION['current'];
				if($_SESSION['current'] === 'X')
				{
					$_SESSION['game_move_x'] = $_SESSION['game_move_x'] + 1;
					$_SESSION['current']     = 'O';
				}
				else
				{
					$_SESSION['game_move_o'] = $_SESSION['game_move_o'] + 1;
					$_SESSION['current']     = 'X';
				}
			}
		}
	}
}

function game_setName()
{
	global $CURRENT_URL;
	if (isset($_POST['setName']))
	{
		$p1 = $_POST['player1'];
		$p2 = $_POST['player2'];
		$_SESSION['playerNames'] = [ 'X' => $p1, 'O' => $p2];
		header("Location:".$CURRENT_URL);
	}

	$el = null;
	$el .= "<input type='text' name='player1' value='". $_SESSION['playerNames']['X'] ."' placeholder='player1' />";
	$el .= "<input type='text' name='player2' value='". $_SESSION['playerNames']['O'] ."' placeholder='player2' />";
	$el .= "<input class='button' type='submit' name='setName' value='save Names' />";
	$el .= "<a href='$CURRENT_URL'>Return</a>";
	return $el;
}

function game_checkWinner()
{
	$g      = $_SESSION['game'];
	$winner = null;
	if(
			($g[1] && $g[1] == $g[2] && $g[2] == $g[3])
	//row 1
		|| 	($g[4] && $g[4] == $g[5] && $g[5] == $g[6])
	//row 2
		|| 	($g[7] && $g[7] == $g[8] && $g[8] == $g[9])
	//row 3

		|| 	($g[1] && $g[1] == $g[4] && $g[4] == $g[7])
	//col 1
		|| 	($g[2] && $g[2] == $g[5] && $g[5] == $g[8])
	//col 2
		|| 	($g[3] && $g[3] == $g[6] && $g[6] == $g[9])
	//col 3

		|| 	($g[1] && $g[1] == $g[5] && $g[5] == $g[9])
	// \
		|| 	($g[3] && $g[3] == $g[5] && $g[5] == $g[7])
	// /

		)
	{
		if ($_SESSION['current'] == 'X')
		{
			$winner = 'O';
		}
		else
		{
			$winner = 'X';
		}
	}
	elseif(!in_array(null, $g))
	{
		$winner = false;
	}
	return $winner;
}

function game_winner()
{
	if($_SESSION['status'] === 'start')
	{
		return null;
	}

	$game_result = game_checkWinner();
	$result      = null;
	$el_changeName ="<p><a href='?action=setName'>Do you want to save your name?</a></p>";
	if($game_result)
	{
		// has one winner
		$_SESSION['lastWinner'] = $game_result;
		$game_result = $_SESSION['playerNames'][$game_result];
		$_SESSION['status']     = 'win';
		$result                 = "<div id='result'>$game_result win !$el_changeName</div>";
		game_save();
	}
	elseif(!in_array(null, $_SESSION['game']))
	{
		// draw
		$_SESSION['status']     = 'draw';
		$_SESSION['lastWinner'] = null;
		$result                 = "<div id='result'>Draw $el_changeName</div>";
		game_save();
	}
	else
	{
		$_SESSION['status'] = 'inprogress';
	}
	return $result;
}

function game_resetBtn()
{
	$result     = null;
	$resetValue = 'Start';
	$resetName  = 'start';
	if($_SESSION['status'] == 'inprogress')
	{
		$resetValue = 'resign';
		$resetName  = 'resign';
	}
	elseif($_SESSION['status'] == 'win' || $_SESSION['status'] == 'draw')
	{
		$resetValue = 'Play Again!';
	}

	if($_SESSION['status'] !== 'start')
	{
		$result = "<input type='submit' name='$resetName' value='$resetValue' id='resetBtn'>";
	}


	return $result;
}


function game_save()
{
	$_SESSION['game_time_end'] = time();

	game_saveResult();
	game_saveResult($_SESSION['playerNames']['X'], true);
	game_saveResult($_SESSION['playerNames']['O'], true);
	game_saveResult($_SESSION['playerNames']['X'].'-'. $_SESSION['playerNames']['O']);

}

function game_saveResult($_player = null, $_single = false)
{
	$_cookiePrefix = 'game_detail';
	if($_player)
	{
		$_cookiePrefix .= '_'. $_player;
	}
	$new_value    = [];
	$detail_list  =
	[
		'count',
		'win',
		'lose',
		'draw',
		'resign',
		'inprogress',
		'total_time',
		'total_move',
		'total_move_win',
	];
	if(isset($_COOKIE[$_cookiePrefix]))
	{
		$new_value = json_decode($_COOKIE[$_cookiePrefix], true);
	}
	$new_value['player'] = $_player;

	foreach ($detail_list as $value)
	{
		if(!isset($new_value[$value]))
		{
			$new_value[$value] = 0;
		}
	}
	$new_value['count']  = $new_value['count'] + 1;


	$game_has_winner = game_checkWinner();
	if($game_has_winner)
	{
		if($_SESSION['playerNames'][$game_has_winner] == $_player)
		{
			$new_value['win'] = $new_value['win'] + 1;
		}
		elseif($_single)
		{
			$new_value['lose'] = $new_value['lose'] + 1;
		}
		else
		{
			$new_value['win'] = $new_value['win'] + 1;
			unset($new_value['lose']);

		}
	}
	elseif($game_has_winner === false)
	{
		$new_value['draw'] = $new_value['draw'] + 1;
	}
	elseif($_single && isset($_SESSION['status']) && $_SESSION['status'] === 'resign' )
	{
		if($_SESSION['playerNames'][$_SESSION['current']] == $_player)
		{
			$new_value['resign'] = $new_value['resign'] + 1;
		}
		else
		{
			$new_value['win'] = $new_value['win'] + 1;
		}
	}
	else
	{
		$new_value['inprogress'] = $new_value['inprogress'] + 1;
	}

	$new_value['total_time'] = $new_value['total_time'] + ($_SESSION['game_time_end'] - $_SESSION['game_time_start']);
	$new_value['total_move'] = $new_value['total_move'] + ($_SESSION['game_move_x'] + $_SESSION['game_move_o']);
	if($game_has_winner)
	{
		$new_value['total_move_win'] = $new_value['total_move_win'] + ($_SESSION['game_move_'. strtolower($game_has_winner)]);
	}

	if($_single)
	{
		$new_value['type'] = 'single';
	}
	elseif($_player)
	{
		$new_value['type'] = 'against';
	}
	else
	{
		$new_value['type'] = 'total';
	}

	if($new_value['player'] === null)
	{
		unset($new_value['player']);
	}
	game_saveCookie($_cookiePrefix, $new_value);
}



function game_saveCookie($_cookieName, $_value)
{
	$_value = json_encode($_value);
	setcookie($_cookieName, $_value,  time() + (86400 * 365));
}


function game_showResult($_type = 'single')
{
	global $CURRENT_URL;
	if(isset($_GET['type']))
	{
		$_type = $_GET['type'];
	}


	$result = null;
	$result .= '<ol id="resultTable">';

	$result .= '<div class="ttitle">';
	$result .= ' <span class="tname">Name</span>';
	$result .= ' <span>Total</span>';
	$result .= ' <span>Win</span>';
	$result .= ' <span>Lose</span>';
	$result .= ' <span>Draw</span>';
	$result .= ' <span>Resing</span>';
	$result .= ' <span class="total">Average Game Time</span>';
	$result .= ' <span class="total">Average Move Win</span>';
	$result .= ' <span class="total">Average Move Time</span>';
	$result .= '</div>';


	foreach ($_COOKIE as $key => $value)
	{
		if(strpos($key, 'game_detail') !== false)
		{
			$value = json_decode($value, true);
			if(isset($value['type']) && $value['type'] == $_type)
			{
				$result .= '<li>';
				if(!isset($value['player']))
				{
					$value['player'] = 'total';
				}
				$result .= '<span class="tname">'.$value['player'].'</span>';
				$result .= '<span>'.$value['count'].'</span>';
				$result .= '<span>'.$value['win'].'</span>';
				$result .= '<span>';
				if(isset($value['lose']))
				{
					$result .= $value['lose'];
				}
				$result .= '</span>';
				$result .= '<span>'.$value['draw'].'</span>';
				$result .= '<span>'.$value['resign'].'</span>';
				$average_game_time = $value['total_time'] / $value['count'];
				if($value['win']>0)
				{
					$average_move_win  = $value['total_move_win'] / $value['win'];
				}
				else
					$average_move_win  = '-';
				$average_move_time = $value['total_time'] / $value['total_move'];

				$result .= '<span class="total">'.$average_game_time.'s</span>';
				$result .= '<span class="total">'.$average_move_win.'</span>';
				$result .= '<span class="total">'.$average_move_time.'s</span>';

				$result .= '</li>';
			}
		}
	}
	$result .= '</ol>';
	$result .= "<div class='row'>";
	$result .= "<a href='$CURRENT_URL?action=showResult&type=total'>Total</a> | ";
	$result .= "<a href='$CURRENT_URL?action=showResult&type=single'>Single</a> | ";
	$result .= "<a href='$CURRENT_URL?action=showResult&type=against'>Against</a> ";
	$result .= "</div>";


	$result .= "<a href='$CURRENT_URL'>Return</a>";

	return $result;
}


?>