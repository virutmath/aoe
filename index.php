<?php

class Player
{
    public $name;
    public $score;
    public $match;

    public function __construct($name, $score, $match)
    {
        $this->name = $name;
        $this->score = $score;
        $this->match = $match;
    }
}

class Match
{
    public $players;
    public $team1;
    public $team2;
    public $score;

    public function __construct($players, $team1, $team2, $score)
    {
        $this->score = $score;
        $this->players = $players;
        $this->team1 = $team1;
        $this->team2 = $team2;
    }
}

function pc_array_power_set($array, $number)
{
    // initialize by adding the empty set
    $results = array(array());

    foreach ($array as $element)
        foreach ($results as $combination)
            array_push($results, array_merge(array($element), $combination));

    $actual_results = [];
    foreach ($results as $result) {
        if (count($result) == $number) {
            $actual_results[] = $result;
        }
    }
    return $actual_results;
}

function calculate_team_score($players = array(), $team = array())
{
    $score = 0;
    foreach ($team as $player_name) {
        $score += $players[$player_name]->score;
    }
    return $score;
}

function rule_score(Player $player, $win, $loss)
{
    if ($player->score < 23) {
        $w = 0.04;
        $l = 0.01;
    } else if ($player->score >= 23 && $player->score < 26) {
        $w = 0.03;
        $l = 0.02;
    } else if ($player->score >= 26 && $player->score < 29) {
        $w = 0.02;
        $l = 0.03;
    } else {
        $w = 0.01;
        $l = 0.04;
    }
    $player->score = $w * $win - $l * $loss + $player->score;
    $player->match = $player->match + $win + $loss;
    return $player;
}

function update_players(Player $player, $list_players = [])
{
    foreach ($list_players as &$item) {
        if ($item->name == $player->name) {
            $item = $player;
        }
    }
    return $list_players;
}

function write_players($list_players)
{
    if (!$list_players) return false;
    file_put_contents('player.json', json_encode($list_players, JSON_PRETTY_PRINT));
}

$players = [];
$data_players = json_decode(file_get_contents('player.json'), 1);
foreach ($data_players as $player) {
    $players[$player['name']] = new Player($player['name'], $player['score'], $player['match']);
}
uasort($players, function ($a, $b) {
    if ($a->score == $b->score) return 0;
    if ($a->score > $b->score) return -1;
    if ($a->score < $b->score) return 1;
});

$list_register = explode("\n", file_get_contents('register.txt'));
foreach ($list_register as &$str) {
    $str = trim($str);
}
$total_gamer = count($list_register);
$team1_count = round($total_gamer / 2);
$team2_count = $total_gamer - $team1_count;

$combination_teams = pc_array_power_set($list_register, $team1_count);
$the_matches = [];
//print_r($combination_teams);
foreach ($combination_teams as $the_team) {
    $team1 = $the_team;
    $team2 = array_values(array_diff($list_register, $the_team));
    $difference = number_format(calculate_team_score($players, $team1) - calculate_team_score($players, $team2),2);
    $the_matches[] = [$team1, $team2, $difference];
}

uasort($the_matches, function ($a, $b) {
    $abs_a = abs($a[2]);
    $abs_b = abs($b[2]);
    if ($abs_a == $abs_b) return 0;
    if ($abs_a > $abs_b) return 1;
    if ($abs_a < $abs_b) return -1;
});

//print_r($the_matches);

$the_matches = array_slice($the_matches, 0, 8);

//ghi danh
if(isset($_POST['action']) && $_POST['action'] == 'diemdanh') {
    $regist_list = trim($_POST['regist_list']);
    if($regist_list) {
        file_put_contents('register.txt', $regist_list);
        header('Location: '.$_SERVER['REQUEST_URI']);
    }
}

$action = isset($_POST['action']) && $_POST['action'] == 'post' && isset($_POST['match']) && isset($_POST['score1']) && isset($_POST['score2']);
if ($action) {
    $match_selected = $the_matches[$_POST['match']];
    $score1 = $_POST['score1'];
    $score2 = $_POST['score2'];
    $team1 = $match_selected[0];
    $team2 = $match_selected[1];
    $updated_players = [];
    foreach ($team1 as $player_name) {
        $the_player = rule_score($players[$player_name], $score1, $score2);
        $updated_players = update_players($the_player, $players);
    }
    foreach ($team2 as $player_name) {
        $the_player = rule_score($players[$player_name], $score2, $score1);
        $updated_players = update_players($the_player, $players);
    }
    //write file
    write_players($updated_players);
}

?>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
</head>
<body>
<div class="container">
    <h2 class="text-danger text-center">GST AOE 2021</h2>
    <div class="row">
        <div class="col">
            <p><b>Rule:</b>
                <br>
                top 4 thắng đc +0,04, thua -0.01<br>
                top 3 thắng +0,03, thua -0.02<br>
                top 2 thắng +0,02, thua - 0.03<br>
                top 1 thắng +0,01, thua -0.04<br>
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <fieldset>
                <h3 class="text-center">Top 8 trận cân nhất</h3>
                <form method="post">
                    <input type="hidden" name="action" value="post">
                    <table class="table">
                        <thead>
                        <tr>
                            <th class="text-center">Trận</th>
                            <th>Đội 1</th>
                            <th>Đội 2</th>
                            <th class="text-center">Hiệu số</th>
                            <th class="text-center">Chọn</th>
                        </tr>
                        </thead>
                        <?php $i = 0;
                        foreach ($the_matches as $offset => $match): ?>
                            <tr>
                                <td class="text-center"><?= ++$i ?></td>
                                <td><?= implode(',', $match[0]) ?></td>
                                <td><?= implode(',', $match[1]) ?></td>
                                <td  class="text-center"><?= $match[2] ?></td>
                                <td class="text-center"><input type="radio" name="match" value="<?= $offset ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3"><b>Cập nhật tỷ số</b></td>
                            <td colspan="2">
                                <button type="submit" class="btn-sm btn btn-primary">Submit</button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5">
                                <div class="form-inline">Đội 1 <input type="text" name="score1"> - <input type="text"
                                                                                                          name="score2">
                                    Đội 2
                                </div>
                            </td>
                        </tr>
                    </table>
                </form>
            </fieldset>
            <hr>
            <h4 class="text-bold text-info">Điểm danh</h4>
            <form method="post">
                <input type="hidden" name="action" value="diemdanh">
                <textarea name="regist_list" class="form-control" id="" cols="30" rows="5" placeholder="Nhập acc, xuống dòng để điểm danh"></textarea>
                <button class="btn btn-warning">Ghi danh</button>
            </form>
        </div>
        <div class="col">
            <fieldset>
                <h3 class="text-center">BXH</h3>
                <table class="table">
                    <thead>
                    <tr>
                        <th class="text-center">STT</th>
                        <th>Tên</th>
                        <th class="text-center">Score</th>
                        <th class="text-center">Match</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 0;
                    foreach ($players as $player): ?>
                        <tr>
                            <td class="text-center"><?= ++$i ?></td>
                            <td><?= $player->name ?></td>
                            <td class="text-center"><?= $player->score ?></td>
                            <td class="text-center"><?= $player->match ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </fieldset>
        </div>
    </div>
</div>


</body>
</html>
