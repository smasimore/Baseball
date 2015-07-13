<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'RetrosheetInclude.php';
include_once __DIR__ .'/../../Sitevar/PlayerCorrections.php';

class RetrosheetPlayerMappingUtils {

    private static $playerIDMap = array();
    private static $espnIDMap = array();

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

    // Try to get an ID but don't fail if one isn't found.
    public static function getIDFromFirstLast($first, $last, $team = null) {
        if ($first === null && $last === null) {
            return null;
        }
        $player_id = null;
        try {
            $player_id = self::getIDFromFirstLastStrict($first, $last, $team);
        } catch (Exception $e) {
            ExceptionUtils::logDisplayEmailException($e, 'd');
        }
        return $player_id;
    }

    private static function getIDFromFirstLastStrict($first, $last, $team) {
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
            if (idx(
                PlayerCorrections::$ambiguousNameTeamMap, $first.$last) !== null
            ) {
                if (idx(
                    PlayerCorrections::$ambiguousNameTeamMap[$first.$last],
                    $team
                    ) === null
                ) {
                    throw new Exception(sprintf(
                        'Update ambiguous name map for %s',
                        $first.$last
                    ));
                }
                return PlayerCorrections::$ambiguousNameTeamMap[$first.$last][$team];
            } else if (idx(
                PlayerCorrections::$nameCorrectionMap, $first.$last) !== null
            ) {
                return PlayerCorrections::$nameCorrectionMap[$first.$last];
            }
            throw new Exception(sprintf(
                'Add first name correction for player %s %s',
                $first,
                $last
            ));
        } else if ($num_results === 1) {
            $data = reset($data);
            return idx($data, 'player_id');
        } else if (idx(
            PlayerCorrections::$ambiguousNameTeamMap, $first.$last) !== null
        ) {
            return PlayerCorrections::$ambiguousNameTeamMap[$first.$last][$team];
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

    private static function createESPNIDMap() {
        self::$espnIDMap = safe_array_column(
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
            if (array_key_exists(
                $espn_id,
                PlayerCorrections::$espnAmbiguousIDMap
            )) {
                self::$playerIDMap[$espn_id]['retrosheet_id'] =
                    PlayerCorrections::$espnAmbiguousIDMap[$espn_id];
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
