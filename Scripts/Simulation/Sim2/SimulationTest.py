import unittest
from Constants import *
from Simulation import Simulation
from Game import StatCategories
from WeightsMutator import WeightsMutator
from Team import Team

class TeamTestCase(unittest.TestCase):

    def __getBatting(self, batting):
        team_batting = {}
        for player in range(1, 10):
            team_batting[str(player)] = {}
            for split, value in batting.iteritems():
                team_batting[str(player)][split] = value
        return team_batting

    def __runGame(self,
        weights,
        batting_h,
        batting_a,
        pitching_h,
        pitching_a):
        input_data = [{
            'gameid' : 'test',
            'game_date' : 'test',
            'home' : 'h_test',
            'away' : 'a_test',
            'batting_h' : self.__getBatting(batting_h),
            'batting_a' : self.__getBatting(batting_a),
            'pitching_h' : pitching_h,
            'pitching_a' : pitching_a
        }]

        # Season doesn't matter since it's a test run.
        game = Simulation(weights, 2014, 'current', 'basic')
        game.setAnalysisRuns(10)
        game.setTestRun(True)
        game.setInputData(input_data)
        return game.run()

    def test_total(self):
        weights = {StatCategories.TOTAL : 1.0}
        b_h = {
            Total.TOTAL : {
                u'pct_strikeout': 0.5,
                u'pct_single': 0.5,
            }
        }
        b_a = {
            Total.TOTAL : {
                u'pct_strikeout': 1,
            }
        }
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'bucket' : 'ERA25',
            'pitcher_vs_batter' : {}
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
            'bucket' : 'ERA50',
            'pitcher_vs_batter' : {}
        }

        results = self.__runGame(weights, b_h, b_a, p_h, p_a)
        self.assertEqual(
            [
                results['gameid'],
                results['sim_game_date'],
                results['weights_mutator'],
                results['home'],
                results['away'],
                results['stats_year'],
                results['home_win_pct'],
                results['weights_i'],
                results['weights'],
                results['stats_type'],
                results['season'],
                results['game_date'],
                results['analysis_runs']
            ],
            [
                'test',
                None,
                None,
                'h_test',
                'a_test',
                'current',
                1.0,
                2,
                'total_100',
                'basic',
                2014,
                'test',
                10
            ]
        )

    def test_home_away(self):
        weights = {StatCategories.HOME_AWAY : 1.0}
        b_h = {
            HomeAway.HOME : {
                u'pct_strikeout': 0.5,
                u'pct_single': 0.5,
            }
        }
        b_a = {
            HomeAway.AWAY : {
                u'pct_strikeout': 1,
            }
        }
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'bucket' : 'ERA25',
            'pitcher_vs_batter' : {}
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
            'bucket' : 'ERA50',
            'pitcher_vs_batter' : {}
        }

        results = self.__runGame(weights, b_h, b_a, p_h, p_a)
        self.assertEqual(
            [
                results['home_win_pct'],
                results['weights_i'],
                results['weights'],
            ],
            [
                1.0,
                3,
                'home_away_100',
            ]
        )

    def test_pitcher_handedness(self):
        weights = {StatCategories.PITCHER_HANDEDNESS : 1.0}
        b_h = {
            PitcherHandedness.RIGHT : {
                u'pct_strikeout': 0.5,
                u'pct_single': 0.5,
            }
        }
        b_a = {
            PitcherHandedness.LEFT : {
                u'pct_strikeout': 1,
            }
        }
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'bucket' : 'ERA25',
            'pitcher_vs_batter' : {}
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
            'bucket' : 'ERA50',
            'pitcher_vs_batter' : {}
        }

        results = self.__runGame(weights, b_h, b_a, p_h, p_a)
        self.assertEqual(
            [
                results['home_win_pct'],
                results['weights_i'],
                results['weights'],
            ],
            [
                1.0,
                4,
                'pitcher_handedness_100',
            ]
        )

    def test_pitcher_era_band(self):
        weights = {StatCategories.PITCHER_ERA_BAND : 1.0}
        b_h = {
            PitcherERABand.ERA50 : {
                u'pct_strikeout': 0.5,
                u'pct_single': 0.5,
            }
        }
        b_a = {
            PitcherERABand.ERA25 : {
                u'pct_strikeout': 1,
            }
        }
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'bucket' : 'ERA25',
            'pitcher_vs_batter' : {}
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
            'bucket' : 'ERA50',
            'pitcher_vs_batter' : {}
        }

        results = self.__runGame(weights, b_h, b_a, p_h, p_a)
        self.assertEqual(
            [
                results['home_win_pct'],
                results['weights_i'],
                results['weights'],
            ],
            [
                1.0,
                5,
                'pitcher_era_band_100',
            ]
        )

    def test_pitcher_vs_batter(self):
        weights = {StatCategories.PITCHER_VS_BATTER : 1.0}
        b_h = {}
        b_a = {}
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'bucket' : 'ERA25',
            'pitcher_vs_batter' : {
                u'pct_strikeout': 1.0,
            }
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
            'bucket' : 'ERA50',
            'pitcher_vs_batter' : {
                u'pct_strikeout': 0.5,
                u'pct_single': 0.5,
            }
        }

        results = self.__runGame(weights, b_h, b_a, p_h, p_a)
        self.assertEqual(
            [
                results['home_win_pct'],
                results['weights_i'],
                results['weights'],
            ],
            [
                1.0,
                6,
                'pitcher_vs_batter_100',
            ]
        )

    def test_reliever_vs_batter(self):
        weights = {StatCategories.PITCHER_VS_BATTER : 1.0}
        b_h = {}
        b_a = {}
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 7,
            'bucket' : 'ERA25',
            'pitcher_vs_batter' : {
                u'pct_strikeout': 1.0,
            },
            'reliever_vs_batter' : {
                u'pct_strikeout': 1.0,
            }
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 7,
            'bucket' : 'ERA50',
            'pitcher_vs_batter' : {
                u'pct_strikeout': 1.0,
            },
            'reliever_vs_batter' : {
                u'pct_single': 0.5,
                u'pct_strikeout': 0.5,
            }
        }

        results = self.__runGame(weights, b_h, b_a, p_h, p_a)
        self.assertEqual(
            [
                results['home_win_pct'],
                results['weights_i'],
                results['weights'],
            ],
            [
                1.0,
                6,
                'pitcher_vs_batter_100',
            ]
        )

    def test_no_reliever_vs_batter(self):
        weights = {StatCategories.PITCHER_VS_BATTER : 1.0}
        b_h = {}
        b_a = {}
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 7,
            'bucket' : 'ERA25',
            'pitcher_vs_batter' : {
                u'pct_strikeout': 1.0,
            },
            'reliever_vs_batter' : {}
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 7,
            'bucket' : 'ERA50',
            'pitcher_vs_batter' : {
                u'pct_single': 0.5,
                u'pct_strikeout': 0.5,
            },
            'reliever_vs_batter' : {}
        }

        results = self.__runGame(weights, b_h, b_a, p_h, p_a)
        self.assertEqual(
            [
                results['home_win_pct'],
                results['weights_i'],
                results['weights'],
            ],
            [
                1.0,
                6,
                'pitcher_vs_batter_100',
            ]
        )

    def test_situation(self):
        weights = {StatCategories.SITUATION : 1.0}
        b_h = {
            Situations.NONE_ON : {
                u'pct_strikeout': 0.5,
                u'pct_single': 0.5,
            },
            Situations.RUNNERS_ON : {
                u'pct_strikeout': 0.5,
                u'pct_single': 0.5,
            },
            Situations.SCORING_POS : {
                u'pct_strikeout': 0.5,
                u'pct_single': 0.5,
            },
            Situations.SCORING_POS_2O : {
                u'pct_strikeout': 0.5,
                u'pct_single': 0.5,
            },
            Situations.BASES_LOADED : {
                u'pct_strikeout': 0.5,
                u'pct_single': 0.5,
            }
        }
        b_a = {
            Situations.NONE_ON : {
                u'pct_strikeout': 1.0,
            },
            Situations.RUNNERS_ON : {
                u'pct_strikeout': 1.0,
            },
            Situations.SCORING_POS : {
                u'pct_strikeout': 1.0,
            },
            Situations.SCORING_POS_2O : {
                u'pct_strikeout': 1.0,
            },
            Situations.BASES_LOADED : {
                u'pct_strikeout': 1.0,
            }
        }
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'bucket' : 'ERA25',
            'pitcher_vs_batter' : {}
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
            'bucket' : 'ERA50',
            'pitcher_vs_batter' : {}
        }

        results = self.__runGame(weights, b_h, b_a, p_h, p_a)
        self.assertEqual(
            [
                results['home_win_pct'],
                results['weights_i'],
                results['weights'],
            ],
            [
                1.0,
                7,
                'situation_100',
            ]
        )

    def test_stadium(self):
        weights = {StatCategories.STADIUM : 1.0}
        b_h = {
            Stadium.STADIUM : {
                u'pct_strikeout': 0.5,
                u'pct_single': 0.5,
            }
        }
        b_a = {
            Stadium.STADIUM : {
                u'pct_strikeout': 1,
            }
        }
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'bucket' : 'ERA25',
            'pitcher_vs_batter' : {}
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
            'bucket' : 'ERA50',
            'pitcher_vs_batter' : {}
        }

        results = self.__runGame(weights, b_h, b_a, p_h, p_a)
        self.assertEqual(
            [
                results['home_win_pct'],
                results['weights_i'],
                results['weights'],
            ],
            [
                1.0,
                8,
                'stadium_100',
            ]
        )

if __name__ == '__main__':
    unittest.main()
