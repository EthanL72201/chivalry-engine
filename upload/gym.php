<?php
/*
	File:		gym.php
	Created: 	4/5/2016 at 12:07AM Eastern Time
	Info: 		Allows players to train their stats at the cost of
				will and energy. Players can replenish their energy
				at the Secondary Curreny Temple, and will can be
				increased by buying new estates.
	Author:		TheMasterGeneral
	Website: 	https://github.com/MasterGeneral156/chivalry-engine
*/
$macropage = ('gym.php');
require("globals.php");
//User is in the infirmary
if ($api->UserStatus($ir['userid'], 'infirmary')) {
    alert("danger", "Unconscious!", "You cannot train while you're in the infirmary.", true, 'index.php');
    die($h->endpage());
}
//User is in the dungeon.
if ($api->UserStatus($ir['userid'], 'dungeon')) {
    alert("danger", "Locked Up!", "You cannot train while you're in the dungeon.", true, 'index.php');
    die($h->endpage());
}
//Convert POST values to Stat Names.
$statnames = array("Strength" => "strength", "Agility" => "agility", "Guard" => "guard", "Labor" => "labor", "All" => "all");
//Training amount is not set, so set to 0.
if (!isset($_POST["amnt"])) {
    $_POST["amnt"] = 0;
}
$_POST["amnt"] = abs($_POST["amnt"]);
echo "<h3>The Gym</h3>";
if (isset($_POST["stat"]) && $_POST["amnt"]) {
    //User trained stat does not exist.
    if (!isset($statnames[$_POST['stat']])) {
        alert("danger", "Uh Oh!", "The stat you've chosen to train does not exist or cannot be trained.", true, 'back');
        die($h->endpage());
    }
    //User fails CSRF check.
    if (!isset($_POST['verf']) || !verify_csrf_code('gym_train', stripslashes($_POST['verf']))) {
        alert('danger', "Action Blocked!", "The action you were trying to do was blocked. It was blocked because you loaded
            another page on the game. If you have not loaded a different page during this time, change your password
            immediately, as another person may have access to your account!", true, 'index.php');
        die($h->endpage());
    }
    $stat = $statnames[$_POST['stat']];
    //User is trying to train using more energy than they have.
    if ($_POST['amnt'] > $ir['energy']) {
        alert("danger", "Uh Oh!", "You are trying to train using more energy than you currently have.", false);
    } else {
        $gain = 0;
        $extraecho = '';
        if ($stat == 'all') {
            $gainstr = $api->UserTrain($userid, 'strength', $_POST['amnt'] / 4);
            $gainagl = $api->UserTrain($userid, 'agility', $_POST['amnt'] / 4);
            $gaingrd = $api->UserTrain($userid, 'guard', $_POST['amnt'] / 4);
            $gainlab = $api->UserTrain($userid, 'labor', $_POST['amnt'] / 4);
        } else {
            $gain = $api->UserTrain($userid, $_POST['stat'], $_POST['amnt']);
        }
        //Update energy left and stat's new count.
        if ($stat != 'all')
            $NewStatAmount = $ir[$stat] + $gain;
        $EnergyLeft = $ir['energy'] - $_POST['amnt'];
        //Strength is chosen stat
        if ($stat == "strength") {
            alert('success', "Success!", "You begin to lift weights. You have gained {$gain} Strength by completing
			    {$_POST['amnt']} sets of weights. You now have {$NewStatAmount} Strength and {$EnergyLeft} Energy left.", false);
            //Have strength selected for the next training.
            $str_select = "selected";
        } //Agility is the chosen stat.
        elseif ($stat == "agility") {
            alert('success', "Success!", "You beging to run laps. You have gained {$gain} Agility by completing
			    {$_POST['amnt']} laps. You now have {$NewStatAmount} Agility and {$EnergyLeft} Energy left.", false);
            //Have agility selected for the next training.
            $agl_select = "selected";
        } //Guard is the chosen stat.
        elseif ($stat == "guard") {
            alert('success', "Success!", "You begin swimming in the pool. You have gained {$gain} Guard by swimming for
			    {$_POST['amnt']} minutes. You now have {$NewStatAmount} Guard and {$EnergyLeft} left.", false);
            //Have guard selected for the next training.
            $grd_select = "selected";
        } //Labor is the chosen stat.
        elseif ($stat == "labor") {
            alert('success', "Success!", "You begin moving boxes around the gym. You have gained {$gain} Labor by moving
                {$_POST['amnt']} sets of boxes. You now have {$NewStatAmount} and {$EnergyLeft} Energy left.", false);
            //Have guard selected for the next training.
            $lab_select = "selected";
        } elseif ($stat == "all") {
            alert('success', "Success!", "You begin training your Strength, Agility, Guard and Labor all at once. You
                have gained {$gainstr} Strength, {$gainagl} Agility, {$gaingrd} Guard and {$gainlab} Labor. You have
                {$EnergyLeft} Energy left.");
            $all_select = "selected";
        }
        //Log the user's training attempt.
        $api->SystemLogsAdd($userid, 'training', "Trained their {$stat} {$_POST['amnt']} times and gained {$gain}.");
        echo "<hr />";
        $ir['energy'] -= $_POST['amnt'];
        if ($stat != 'all')
            $ir[$stat] += $gain;
    }
}
//Small logic to keep the last trained stat selected.
if (!isset($str_select)) {
    $str_select = '';
}
if (!isset($agl_select)) {
    $agl_select = '';
}
if (!isset($grd_select)) {
    $grd_select = '';
}
if (!isset($lab_select)) {
    $lab_select = '';
}
if (!isset($all_select)) {
    $all_select = '';
}
//Grab the user's stat ranks.
$ir['strank'] = get_rank($ir['strength'], 'strength');
$ir['agirank'] = get_rank($ir['agility'], 'agility');
$ir['guarank'] = get_rank($ir['guard'], 'guard');
$ir['labrank'] = get_rank($ir['labor'], 'labor');
$ir['all_four'] = ($ir['labor'] + $ir['strength'] + $ir['agility'] + $ir['guard']);
$ir['af_rank'] = get_rank($ir['all_four'], 'all');
//Request CSRF code.
$code = request_csrf_html('gym_train');
echo "Choose the stat you wish to train, and enter how many times you wish to train it. You can train up to {$ir['energy']} times.<hr />
<table class='table table-bordered'>
	<tr>
		<form action='gym.php' method='post'>
			<th>
			    Stat
            </th>
			<td>
				<select type='dropdown' name='stat' class='form-control'>
					<option {$str_select} value='Strength'>
					    Strength (Have {$ir['strength']}, Ranked: {$ir['strank']})
                    </option>
					<option {$agl_select} value='Agility'>
					    Agility (Have {$ir['agility']}, Ranked: {$ir['agirank']})
                    </option>
					<option {$grd_select} value='Guard'>
					    Guard (Have {$ir['guard']}, Ranked: {$ir['guarank']})
                    </option>
					<option {$lab_select} value='Labor'>
					    Labor (Have {$ir['labor']}, Ranked: {$ir['labrank']})
                    </option>
					<option {$all_select} value='All'>
					    All Four (Have {$ir['all_four']}, Ranked: {$ir['af_rank']})
                    </option>
				</select>
			</td>
	</tr>
	<tr>
		<th>
		    Training Duration
        </th>
		<td>
		    <input type='number' class='form-control' min='1' max='{$ir['energy']}' name='amnt' value='{$ir['energy']}' />
        </td>
	</tr>
	<tr>
		<td colspan='2'>
		    <input type='submit' class='btn btn-primary' value='Train' />
        </td>
	</tr>
	    {$code}
	    </form>
</table>";
$h->endpage();