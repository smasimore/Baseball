<?php
//Copyright 2014, Saber Tooth Ventures, LLC

if (!defined('HOME_PATH')) {
    include('/Users/constants.php');
    include(HOME_PATH.'Scripts/Include/Enum.php');
}

class Teams {

    public static $teamNames = array(
        'Atlanta' => 'Braves',
        'Philadelphia' => 'Phillies',
        'Washington' => 'Nationals',
        'NY Mets' => 'Mets',
        'Miami' => 'Marlins',
        'St. Louis' => 'Cardinals',
        'Cincinnati' => 'Reds',
        'Pittsburgh' => 'Pirates',
        'Chicago Cubs' => 'Cubs',
        'Milwaukee' => 'Brewers',
        'Arizona' => 'Diamondbacks',
        'San Francisco' => 'Giants',
        'Colorado' => 'Rockies',
        'San Diego' => 'Padres',
        'LA Dodgers' => 'Dodgers',
        'Boston' => 'Red-Sox',
        'Baltimore' => 'Orioles',
        'NY Yankees' => 'Yankees',
        'Tampa Bay' => 'Rays',
        'Toronto' => 'Blue-Jays',
        'Detroit' => 'Tigers',
        'Cleveland' => 'Indians',
        'Kansas City' => 'Royals',
        'Minnesota' => 'Twins',
        'Chicago Sox' => 'White-Sox',
        'Oakland' => 'Athletics',
        'Texas' => 'Rangers',
        'Seattle' => 'Mariners',
        'LA Angels' => 'Angels',
        'Houston' => 'Astros'
    );

    public static $teamAbbreviations = array(
        'Atlanta' => 'ATL',
        'Philadelphia' => 'PHI',
        'Washington' => 'WSH',
        'NY Mets' => 'NYM',
        'Miami' => 'MIA',
        'St. Louis' => 'STL',
        'Cincinnati' => 'CIN',
        'Pittsburgh' => 'PIT',
        'Chicago Cubs' => 'CHC',
        'Milwaukee' => 'MIL',
        'Arizona' => 'ARI',
        'San Francisco' => 'SF',
        'Colorado' => 'COL',
        'San Diego' => 'SD',
        'LA Dodgers' => 'LAD',
        'Boston' => 'BOS',
        'Baltimore' => 'BAL',
        'NY Yankees' => 'NYY',
        'Tampa Bay' => 'TB',
        'Toronto' => 'TOR',
        'Detroit' => 'DET',
        'Cleveland' => 'CLE',
        'Kansas City' => 'KC',
        'Minnesota' => 'MIN',
        'Chicago Sox' => 'CHW',
        'Oakland' => 'OAK',
        'Texas' => 'TEX',
        'Seattle' => 'SEA',
        'LA Angels' => 'LAA',
        'Houston' => 'HOU'
    );

    public static function getTeamNameFromAbbr($abbr) {
        $city = self::getTeamCityFromAbbreviation($abbr);
        return self::$teamNames[$city];
    }

    public static function getTeamCityFromName($team) {
        $team = self::getStandardTeamName($team);
        return array_search($team, self::$teamNames);
    }

    public static function getTeamCityFromAbbreviation($abbr) {
        $abbr = self::getStandardTeamAbbr($abbr);
        return array_search($abbr, self::$teamAbbreviations);
    }

    public static function getTeamAbbreviationFromCity($city) {
        $city = self::getStandardTeamCity($city);
        return self::$teamAbbreviations[$city];
    }

    public static function getTeamAbbreviationFromName($team) {
        $city = self::getTeamCityFromName($team);
        return self::getTeamAbbreviationFromCity($city);
    }

    public static function getStandardTeamName($team) {
        $team = ucwords($team);
        // ABC order based on $team name.
        switch ($team) {
            case 'Anaheim':
                $team = 'Angels';
                break;
            case 'Blue Jays':
            case 'Jays':
                $team = 'Blue-Jays';
                break;
            case 'Red Sox':
            case 'Boston Red Sox':
                $team = 'Red-Sox';
                break;
            case 'White Sox':
            case 'Chicago White Sox':
                $team = 'White-Sox';
                break;
        }
        if (!in_array($team, self::$teamNames)) {
            throw new Exception('Invalid Team Name');
        }
        return $team;
    }

    public static function getStandardTeamCity($city) {
        $city = ucwords($city);
        // ABC order based on $city.
        switch ($city) {
            case "Chi. Cubs":
                $city = "Chicago Cubs";
                break;
            case "Chi. White Sox":
                $city = "Chicago Sox";
                break;
            case "L.A. Angels":
                $city = "LA Angels";
                break;
            case "L.A. Dodgers":
                $city = "LA Dodgers";
                break;
            case "N.Y. Mets":
                $city = "NY Mets";
                break;
            case "N.Y. Yankees":
                $city = "NY Yankees";
                break;
        }
        if (!array_key_exists($city, self::$teamNames)) {
            throw new Exception('Invalid Team City');
        }
        return $city;
    }

    public static function getStandardTeamAbbr($abbr) {
        $abbr = strtoupper($abbr);
        switch ($abbr) {
            case 'CHN':
                $abbr = 'CHC';
                break;
            case 'CHA':
            case 'CWS':
                $abbr = 'CHW';
                break;
            case 'KCA':
            case 'KAN':
                $abbr = 'KC';
                break;
            case 'ANA':
                $abbr = 'LAA';
                break;
            case 'LA':
            case 'LAN':
                $abbr = 'LAD';
                break;
            case 'FLA':
                $abbr = 'MIA';
                break;
            case 'NYN':
                $abbr = 'NYM';
                break;
            case 'NYA':
                $abbr = 'NYY';
                break;
            case 'SDN':
                $abbr = 'SD';
                break;
            case 'SFN':
                $abbr = 'SF';
                break;
            case 'SLN':
                $abbr = 'STL';
                break;
            case 'TBA':
            case 'TAM':
                $abbr = 'TB';
                break;
            case 'WAS':
                $abbr = 'WSH';
                break;
        }
        if (!in_array($abbr, self::$teamAbbreviations)) {
            throw new Exception('Invalid Team Abbreviation');
        }
        return $abbr;
    }

    public static function getAllRetrosheetTeamAbbrs($season) {
        $sql = "SELECT DISTINCT TEAM_ID
            FROM teams
            WHERE year_id = $season";
        $data = exe_sql(DATABASE, $sql);
        return array_keys(index_by($data, 'TEAM_ID'));
    }
}

?>
