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

    public static function getTeamCityFromName($team) {
        $team = self::getStandardTeamName($team);
        return array_search($team, self::$teamNames);
    }

    public static function getTeamAbbreviationFromCity($team) {
        if (!array_key_exists($team, self::$teamAbbreviations)) {
            throw new Exception('Invalid Team City');
        }
        return self::$teamAbbreviations[$team];
    }

    public static function getTeamAbbreviationFromName($team) {
        $city = self::getTeamCityFromName($team);
        return self::getTeamAbbreviationFromCity($city);
    }

    public static function getStandardTeamName($team) {
        $team = ucwords($team);
        switch ($team) {
            case 'Red Sox':
            case 'Boston Red Sox':
                $team = 'Red-Sox';
                break;
            case 'White Sox':
            case 'Chicago White Sox':
                $team = 'White-Sox';
                break;
            case 'Blue Jays':
            case 'Jays':
                $team = 'Blue-Jays';
                break;
            case 'Anaheim':
                $team = 'Angels';
                break;
        }
        if (!in_array($team, self::$teamNames)) {
            throw new Exception('Invalid Team Name');
        }
        return $team;
    }

    public static function getStandardTeamAbbr($abbr) {
        $abbr = strtoupper($abbr);
        switch ($abbr) {
            case 'WAS':
                $abbr = 'WSH';
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
            case 'SLN':
                $abbr = 'STL';
                break;
            case 'CHN':
                $abbr = 'CHC';
                break;
            case 'SFN':
                $abbr = 'SF';
                break;
            case 'SDN':
                $abbr = 'SD';
                break;
            case 'NYA':
                $abbr = 'NYY';
                break;
            case 'TBA':
                $abbr = 'TB';
                break;
            case 'KCA':
                $abbr = 'KC';
                break;
            case 'CHA':
                $abbr = 'CHW';
                break;
            case 'ANA':
                $abbr = 'LAA';
                break;
        }
        if (!in_array($abbr, self::$teamAbbreviations)) {
            throw new Exception('Invalid Team Abbreviation');
        }
        return $abbr;
    }
}

?>
