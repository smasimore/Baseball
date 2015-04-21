<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

if (!defined('HOME_PATH')) {
    include('/Users/constants.php');
}
include_once(HOME_PATH.'Scripts/Include/RetrosheetInclude.php');

class RetrosheetPlayerMapping {

    private static $playerIDMap = array();
    private static $espnIDMap = array();
    private static $espnAmbiguousIDMap = array(
        31125 => 'torra002',
        31984 => 'rasmc002',
        30578 => 'casts002',
        30535 => 'lin-c001',
        29248 => 'kaaik001',
        29248 => 'kaaik001',
        30599 => 'gonzm004',
        29310 => 'gonzm003',
        29307 => 'negrk001',
        30676 => 'darnc001',
        30455 => 'lo--c001',
        29434 => 'devrc001',
        31663 => 'delaj002',
        6209 => 'wangc001',
        32701 => 'delae002',
        31839 => 'hellj002',
        33094 => 'lim-c001',
        31051 => 'delar003',
        31393 => 'gelts001',
        30978 => 'delad001',
        31059 => 'delej002',
        30976 => 'defrj001',
        32052 => 'lee-c004',
        32048 => 'chenw001',
        31518 => 'adcon001',
        30412 => 'roe-c001',
        29334 => 'osuls001',
        29081 => 'odayd001',
        28549 => 'oflae001',
        6183 => 'rowlr002',
        5906 => 'delaj001',
        5981 => 'rodre002',
        29609 => 'rodre003',
        3610 => 'carpc002',
        31088 => 'carpc003',
        30975 => 'martl002',
        5618 => 'martl001',
        30550 => 'younm004',
        28887 => 'thomr006',
        28606 => 'maldc003',
        29260 => 'grayj002',
        32008 => 'diazj003',
        4599 => 'diazj001',
        5498 => 'gonze001',
        29127 => 'gonze003',
        31213 => 'jackr005',
        3796 => 'jackr004',
        30007 => 'rodrh002',
        2714 => 'rodrh001',
        31331 => 'rodrh003',
        3211 => 'garcf001',
        4007 => 'garcf002',
        32801 => 'ramij003',
        32603 => 'ramij01',
        32068 => 'eatoa002',
        31996 => 'lombs002',
        5411 => 'reyej001',
        5890 => 'bautj002',
        6242 => 'cruzn002',
        28721 => 'braur002',
        5353 => 'lee-c003',
        31340 => 'martc006',
        29486 => 'cartc002',
        6073 => 'younc003',
        31409 => 'browa003',
        3916 => 'gonza002',
        28516 => 'gwynt002',
        5357 => 'rodrf003',
        6261 => 'bakes002',
        6503 => 'ramir003',
        29698 => 'carpd001',
        30007 => 'rodrh002',
        30201 => 'millb001',
        30648 => 'hatcc002',
        30020 => 'dejei002',
        29106 => 'burre001',
        30474 => 'hills004',
        28738 => 'vandr001',
        29481 => 'delof001',
        29704 => 'pomes001',
        30268 => 'pinam001',
        30500 => 'delre001',
        32041 => 'fickc001',
        31305 => 'carpd002',
        31983 => 'ramie004',
        33089 => 'garcl005',
        29200 => 'murpd006',
        6514 => 'younc004',
        32801 => 'ramij003',
        30584 => 'sanct001',
        31477 => 'murpj001',
        30583 => 'stanm004',
        31684 => 'dickc002',
        32082 => 'grays001',
        30714 => 'vanss001',
        29200 => 'murpd006',
        30640 => 'butlj002',
        32582 => 'ryu-h001',
        33087 => 'diazj004',
        30664 => 'garcl004',
        5970 => 'uptob001',
        31474 => 'almoa001',
        29951 => 'darnt001',
        6205 => 'choos001',
        31193 => 'dendm001',
        28728 => 'deaza001',
        29437 => 'tolls001',
        32011 => 'perej002',
        29643 => 'jimel001',
        31970 => 'jimel002',
        // The following played pre-2013 but are not in Retrosheet.
        31446 => 'kierk01',
        6272 => 'carlj01',
        30291 => 'asenj01',
        29221 => 'carpd01'
    );
    private static $ambiguousNameTeamMap = array(
        'chriscarpenter' => array(
            'STL' => 'carpc002',
            'BOS' => 'carpc003'
        ),
        'davidcarpenter' => array(
            'ATL' => 'carpd001',
            'LAA' => 'carpd002'
        ),
        'joseramirez' => array(
            'NYY' => 'ramij01',
            'CLE' => 'ramij003'
        ),
        'michaeltaylor' => array(
            'CHW' => 'taylm001',
            'WSH' => 'taylm01'
        ),
        'luisjimenez' => array(
            'LAA' => 'jimel002',
            'SEA' => 'jimel001'
        ),
        'henryrodriguez' => array(
            'MIA' => 'rodrh002',
            'CIN' => 'rodrh003'
        ),
        'juanperez' => array(
            'SF' => 'perej002',
            'TOR' => 'perej001'
        ),
        'chrisyoung' => array(
            'SEA' => 'younc003',
            'OAK' => 'younc004'
        )
    );

    // This function should only be called by the players.php script with
    // players and their corresponding ESPN IDs.
    public static function createPlayerIDMap($player_arr) {
        self::$playerIDMap = index_by($player_arr, 'espn_id');
        self::createESPNIDMap();
        self::checkCurrentPlayersTable();
        self::getIDsFromRetrosheet();
        self::correctMissingRetrosheetIDs('bat_id');
        self::correctMissingRetrosheetIDs('pit_id');
        self::createRetrosheetIDs();
        self::writeToPlayersTable();
    }

    public static function getPlayerIDMap($player_arr) {
        self::$playerIDMap = index_by($player_arr, 'first', 'last');
        self::checkCurrentPlayersTable();
        return self::$playerIDMap;
    }

    public static function getIDFromESPNID($espn_id) {
       $sql = sprintf(
            "SELECT *
            FROM %s
            WHERE espn_id = %d",
            'players',
            $espn_id
        );
        $data = exe_sql(DATABASE, $sql);
        $data = reset($data);
        return idx($data, 'player_id');
    }

    public static function getIDFromFirstLast($first, $last, $team = null) {
        $first = format_for_mysql($first);
        $last = format_for_mysql($last);
        $sql = sprintf(
            "SELECT *
            FROM %s
            WHERE first = '%s'
            AND last = '%s'",
            'players',
            $first,
            $last
        );
        $data = exe_sql(DATABASE, $sql);
        $num_results = count($data);
        if ($num_results === 0) {
            // Check to make sure it's not a first-name discrepency.
            $sql = sprintf(
                "SELECT *
                FROM %s
                WHERE last = '%s'",
                'players',
                $last
            );
            $last_name_data = exe_sql(DATABASE, $sql);
            if (count($last_name_data) !== 0) {
                if (idx(self::$ambiguousNameTeamMap, $first.$last) !== null) {
                    return self::$ambiguousNameTeamMap[$first.$last][$team];
                }
                /*
                throw new Exception(sprintf(
                    'Add first name correction for player %s %s',
                    $first,
                    $last
                ));
                 */
            }
            return null;
        } else if ($num_results === 1) {
            $data = reset($data);
            return idx($data, 'player_id');
        } else if (idx(self::$ambiguousNameTeamMap, $first.$last) !== null) {
            return self::$ambiguousNameTeamMap[$first.$last][$team];
        }
        // Otherwise throw an exception.
        throw new Exception(sprintf(
            'Multiple players named %s %s please add team mapping',
            $first,
            $last
        ));
    }

    private static function writeToPlayersTable() {
        $colheads = array(
            'player_id',
            'espn_id',
            'team',
            'first',
            'last',
            'ds'
        );
        $sql_insert = array();
        foreach (self::$playerIDMap as $player) {
            if (idx($player, 'is_new') !== 1) {
                continue;
            }
            $player['player_id'] = $player['retrosheet_id'];
            $player['ds'] = date('Y-m-d');
            $sql_insert[] = $player;
        }
        $insert_table = 'players';
        if ($sql_insert !== array()) {
            multi_insert(
                DATABASE,
                $insert_table,
                $sql_insert,
                $colheads
            );
            logInsert($insert_table);
        } else {
            // Log even if there aren't new players so the daily script doesn't
            // fail.
            logInsert($insert_table, true);
        }
        return;
    }

    private static function checkCurrentPlayersTable() {
        $sql = sprintf(
            'SELECT *
            FROM %s',
            'players'
        );
        $data = exe_sql(DATABASE, $sql);
        // Check if it's from ESPN by checking the $playerIDMap for
        // the espn array key.
        if (array_key_exists('espn_id', reset(self::$playerIDMap))) {
            $data = index_by($data, 'espn_id');
            foreach ($data as $espn_id => $player) {
                if (array_key_exists($espn_id, self::$playerIDMap)) {
                    self::$playerIDMap[$espn_id]['retrosheet_id'] =
                        $player['player_id'];
                }
            }
        } else {
            $data = index_by($data, 'first', 'last');
            $ambiguous_names = self::getAmbiguousNames();
            foreach (self::$playerIDMap as $player_index => $player) {
                // TODO(cert) something here with ambiguous names
                if (array_key_exists($player_index, $ambiguous_names)) {
                    // do something here
                }
                if (array_key_exists($player_index, $data)) {
                    self::$playerIDMap[$player_index]['player_id'] =
                        $data[$player_index]['player_id'];
                }
            }
        }
    }

    // If any players are new create a Retrosheet ID for them. Remove a 0
    // (a) so we don't overlap with retrosheet and (b) so we know these are
    // newer names (in the event we combine back with Retrosheet data, etc.
    private static function createRetrosheetIDs() {
        $players = self::getRemainingPlayers();
        $new_ids = array();
        foreach ($players as $player) {
            if ($player['retrosheet_include'] === 1) {
                $espn_id = $player['espn_id'];
                throw new Exception("Should not make new id for $espn_id");
            }
            $id_start = substr($player['last'], 0, 4) . substr($player['first'], 0 , 1);
            $id_is_new = false;
            $id_end = 1;
            while (!$id_is_new) {
                $id = $id_start . '0' . $id_end;
                // Check to make sure this ID doesn't already exist.
                $sql = sprintf(
                    "SELECT *
                    FROM %s
                    WHERE player_id = '%s'",
                    'players',
                    $id
                );
                $data = exe_sql(DATABASE, $sql);
                $id_end++;
                $id_is_new = $data == null && !array_key_exists($id, $new_ids);
            }
            $new_ids[$id] = $id;
            self::$playerIDMap[$player['espn_id']]['retrosheet_id'] = $id;
        }
    }

    private static function getRemainingPlayers() {
        return array_filter(
            self::$playerIDMap,
            function($x) {
                return !idx($x, 'retrosheet_id') && !idx($x, 'player_id');
            }
        );
    }

    private static function getRemainingRetrosheetPlayers() {
        return array_filter(
            self::$playerIDMap,
            function($x) {
                return !idx($x, 'retrosheet_id')
                    && idx($x, 'retrosheet_include') === 1;
            }
        );
    }

    // Some retrosheet players (especially in 2013) are missing from the id
    // table so check for those.
    private static function correctMissingRetrosheetIDs($type) {
        $names_arr = self::getRemainingRetrosheetPlayers();
        $sql = sprintf(
            'SELECT count(1) as instances,
                %s
            FROM
                (SELECT DISTINCT %s
                FROM %s
                WHERE season = 2013 AND (FALSE',
                $type,
                $type,
                RetrosheetTables::EVENTS
        );
        foreach ($names_arr as $player) {
            $first = $player['first'];
            $last = $player['last'];
            $id_start = substr($last, 0, 4) . substr($first, 0 , 1);
            $sql .= " OR $type like '$id_start%'";
        }
        $sql .= ")) a GROUP BY substr($type, 1, 5) HAVING instances < 2";
        $nondupe_players = exe_sql(DATABASE, $sql);
        $nondupe_players = array_keys(index_by($nondupe_players, $type));
        foreach ($names_arr as $player) {
            $espn_id = $player['espn_id'];
            $first = $player['first'];
            $last = $player['last'];
            $id_start = substr($last, 0, 4) . substr($first, 0 , 1);
            foreach ($nondupe_players as $id) {
                if (substr($id, 0, 5) === $id_start) {
                    self::$playerIDMap[$espn_id]['retrosheet_id'] = $id;
                }
            }
        }
        return;
    }

    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if (!isset($value[$columnKey])) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            }
            else {
                if (!isset($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if (!is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }

    private static function createESPNIDMap() {
        self::$espnIDMap = self::array_column(
            self::$playerIDMap,
            'firstlast',
            'espn_id'
        );
        if (count(array_unique(self::$espnIDMap)) < count(self::$espnIDMap)) {
            // Count number of ids per name and filter out unique using
            // array_diff (where value = 1).
            $players = array_count_values(self::$espnIDMap);
            $dupes = array_diff($players, array(1));
            throw new Exception(sprintf(
                'Duplicate player names: %s',
                arrayToString($dupes)
            ));
        }
    }

    private static function validateShouldHaveRetrosheetID($espn_id) {
        self::$playerIDMap[$espn_id]['retrosheet_include'] = 0;
        $url = "http://espn.go.com/mlb/player/stats/_/id/$espn_id";
        $source = scrape($url);
        for ($year = 2009; $year < 2014; $year++) {
            $html_2 = sprintf('<td align="left">%d</td>', $year);
            $html_1 = sprintf('<td>%d</td>', $year);
            if (strpos($source, $html_1) !== false
            || strpos($source, $html_2) !== false) {
                self::$playerIDMap[$espn_id]['retrosheet_include'] = 1;
            }
        }
        return self::$playerIDMap[$espn_id];
    }

    private static function getIDsFromRetrosheet() {
        $sql = sprintf(
            'SELECT id as retrosheet_id,
                lcase(first) as first,
                lcase(last) as last,
                lcase(concat(first,last)) as firstlast
            FROM %s
            WHERE substr(debut, -4) >= 1985 AND (FALSE',
            RetrosheetTables::ID
        );
        $ambiguous_names = self::getAmbiguousNames();
        $remaining_players = index_by(self::getRemainingPlayers(), 'espn_id');
        foreach ($remaining_players as $espn_id => $player) {
            self::$playerIDMap[$espn_id]['is_new'] = 1;
            self::validatePlayerName($player);
            // Now check to see if they are already in the exception map.
            if (array_key_exists($espn_id, self::$espnAmbiguousIDMap)) {
                self::$playerIDMap[$espn_id]['retrosheet_id'] =
                    self::$espnAmbiguousIDMap[$espn_id];
                continue;
            }
            // Don't check for a retrosheet id if they won't have one (i.e.
            // they started playing in 2014 or later. Mark these accounts so we
            // don't have to recheck for this later.
            $player = self::validateShouldHaveRetrosheetID($espn_id);
            $player_index = $player['first'] . $player['last'];
            // Ensure there aren't players with potentially ambiguous ids.
            if (array_key_exists($player_index, $ambiguous_names)) {
                throw new Exception("Duplicate Name for $player_index");
            }
            // Now remove people who shouldn't have retrosheet id's.
            if ($player['retrosheet_include'] === 0) {
                continue;
            }
            // Now add the remaining players to the sql query.
            $where_info = sprintf(
                "(last = '%s' AND first = '%s')",
                $player['last'],
                $player['first']
            );
            $sql .= ' OR ' . $where_info;
        }
        $sql .= ')';
        $retro_data = exe_sql(DATABASE, $sql);
        $remaining_players = index_by(self::getRemainingPlayers(), 'first', 'last');
        foreach ($retro_data as $rs_player) {
            $firstlast = format_for_mysql($rs_player['firstlast']);
            $espn_id = $remaining_players[$firstlast]['espn_id'];
            self::$playerIDMap[$espn_id]['retrosheet_id'] = $rs_player['retrosheet_id'];
            self::$playerIDMap[$espn_id]['is_new'] = 1;
        }
    }

    // Ensure external sites are providing valid first and last names.
    private static function validatePlayerName($player) {
        if (!($player['last'] && $player['first'])) {
            $missing_name = $player['last'] ? 'first' : 'last';
            throw new Exception(sprintf(
                "Player %s is missing his %s name \n Player Array: \n %s",
                $player['first'] . $player['last'],
                $missing_name,
                arrayToString($player)
            ));
        }
    }

    private static function getAmbiguousNames() {
        $sql = sprintf(
            'SELECT count(1) as instances,
                first,
                last
            FROM %s
            GROUP by first, last
            HAVING instances > 1
            UNION ALL
            SELECT count(1) as instances,
                lcase(first) as first,
                lcase(last) as last
            FROM %s
            WHERE substr(debut, -4) >= 1980
            GROUP BY first, last
            HAVING instances > 1',
            'players',
            RetrosheetTables::ID
        );
        $data = exe_sql(DATABASE, $sql);
        return index_by($data, 'first', 'last');
    }
}

?>
