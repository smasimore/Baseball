import unittest
from Constants import *
from Simulation import Simulation
from Game import StatCategories
from WeightsMutator import WeightsMutator
from Team import Team

class SimulationTestCase(unittest.TestCase):

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
        pitching_a,
        use_reliever = False):
        input_data = [{
            'gameid' : 'test',
            'game_date' : 'test',
            'rand_bucket' : 0,
            'home' : 'h_test',
            'away' : 'a_test',
            'batting_h' : self.__getBatting(batting_h),
            'batting_a' : self.__getBatting(batting_a),
            'pitching_h' : pitching_h,
            'pitching_a' : pitching_a
        }]

        # Season doesn't matter since it's a test run.
        game = Simulation(weights, 2014, 'season', 'basic')
        game.setAnalysisRuns(10)
        game.setTestRun(True)
        game.setInputData(input_data)
        game.setUseReliever(use_reliever)
        game.setDebugLogging(False)
        return game.run()

    def test_b_total(self):
        weights = {StatCategories.B_TOTAL : 1.0}
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
            'pitcher_vs_batter' : {}
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
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
                'season',
                1.0,
                6,
                'b_total_100',
                'basic',
                2014,
                'test',
                10
            ]
        )

    def test_b_home_away(self):
        weights = {StatCategories.B_HOME_AWAY : 1.0}
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
            'pitcher_vs_batter' : {}
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
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
                1,
                'b_home_away_100',
            ]
        )

    def test_b_pitcher_handedness(self):
        weights = {StatCategories.B_PITCHER_HANDEDNESS : 1.0}
        b_h = {
            Handedness.RIGHT : {
                u'pct_strikeout': 0.5,
                u'pct_single': 0.5,
            }
        }
        b_a = {
            Handedness.LEFT : {
                u'pct_strikeout': 1,
            }
        }
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {}
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
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
                'b_pitcher_handedness_100',
            ]
        )

    def test_b_situation(self):
        weights = {StatCategories.B_SITUATION : 1.0}
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
            'pitcher_vs_batter' : {}
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
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
                'b_situation_100',
            ]
        )

    def test_b_stadium(self):
        weights = {StatCategories.B_STADIUM : 1.0}
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
            'pitcher_vs_batter' : {}
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
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
                'b_stadium_100',
            ]
        )

    def test_p_total(self):
        weights = {StatCategories.P_TOTAL : 1.0}
        b_h = {}
        b_a = {}
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
                Total.TOTAL : {
                    u'pct_strikeout': 1,
                }
            }
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
                Total.TOTAL : {
                    u'pct_strikeout': 0.5,
                    u'pct_single': 0.5,
                }
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
                7,
                'p_total_100',
            ]
        )

    def test_p_home_away(self):
        weights = {StatCategories.P_HOME_AWAY : 1.0}
        b_h = {}
        b_a = {}
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
                HomeAway.HOME : {
                    u'pct_strikeout': 1,
                }
            }
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
                HomeAway.AWAY : {
                    u'pct_strikeout': 0.5,
                    u'pct_single': 0.5,
                }
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
                8,
                'p_home_away_100',
            ]
        )

    def test_p_batter_handedness(self):
        weights = {StatCategories.P_BATTER_HANDEDNESS : 1.0}
        b_h = {'handedness' : 'L'}
        b_a = {'handedness' : 'R'}
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
                Handedness.RIGHT : {
                    u'pct_strikeout': 1,
                }
            }
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
                Handedness.LEFT : {
                    u'pct_strikeout': 0.5,
                    u'pct_single': 0.5,
                }
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
                9,
                'p_batter_handedness_100',
            ]
        )

    def test_p_batter_handedness_switch(self):
        weights = {StatCategories.P_BATTER_HANDEDNESS : 1.0}
        b_h = {'handedness' : 'B'}
        b_a = {'handedness' : 'B'}
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
                Handedness.RIGHT : {
                    u'pct_strikeout': 1,
                }
            }
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
                Handedness.LEFT : {
                    u'pct_strikeout': 0.5,
                    u'pct_single': 0.5,
                }
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
                9,
                'p_batter_handedness_100',
            ]
        )

    def test_p_situation(self):
        weights = {StatCategories.P_SITUATION : 1.0}
        b_h = {}
        b_a = {}
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
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
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
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
                11,
                'p_situation_100',
            ]
        )

    def test_p_stadium(self):
        weights = {StatCategories.P_STADIUM : 1.0}
        b_h = {}
        b_a = {}
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
                Stadium.STADIUM : {
                    u'pct_strikeout': 1,
                }
            }
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
                Stadium.STADIUM : {
                    u'pct_strikeout': 0.5,
                    u'pct_single': 0.5,
                }
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
                12,
                'p_stadium_100',
            ]
        )

    def test_p_total_reliever(self):
        weights = {StatCategories.P_TOTAL : 1.0}
        b_h = {}
        b_a = {}
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
                Total.TOTAL : {
                    u'pct_strikeout': 1,
                }
            }
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 5,
            'pitcher_vs_batter' : {
                Total.TOTAL : {
                    u'pct_strikeout': 1,
                }
            },
            'reliever_vs_batter' : {
                Total.TOTAL : {
                    u'pct_strikeout': 0.5,
                    u'pct_single': 0.5,
                }
            }
        }

        results = self.__runGame(weights, b_h, b_a, p_h, p_a, True)
        self.assertEqual(
            [
                results['home_win_pct'],
                results['weights_i'],
                results['weights'],
            ],
            [
                1.0,
                7,
                'p_total_100',
            ]
        )

    def test_p_total_no_reliever(self):
        weights = {StatCategories.P_TOTAL : 1.0}
        b_h = {}
        b_a = {}
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 99,
            'pitcher_vs_batter' : {
                Total.TOTAL : {
                    u'pct_strikeout': 1,
                }
            }
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 5,
            'pitcher_vs_batter' : {
                Total.TOTAL : {
                    u'pct_single': 0.5,
                    u'pct_strikeout': 0.5,
                }
            },
            'reliever_vs_batter' : {}
        }

        results = self.__runGame(weights, b_h, b_a, p_h, p_a, True)
        self.assertEqual(
            [
                results['home_win_pct'],
                results['weights_i'],
                results['weights'],
            ],
            [
                1.0,
                7,
                'p_total_100',
            ]
        )

    def test_p_total_force_no_reliever(self):
        weights = {StatCategories.P_TOTAL : 1.0}
        b_h = {}
        b_a = {}
        p_h = {
            'handedness' : 'L',
            'avg_innings' : 0,
            'pitcher_vs_batter' : {
                Total.TOTAL : {
                    u'pct_strikeout': 1,
                }
            },
            'reliever_vs_batter' : {
                Total.TOTAL : {
                    u'pct_single': 0.5,
                    u'pct_strikeout': 0.5,
                }
            }
        }
        p_a = {
            'handedness' : 'R',
            'avg_innings' : 0,
            'pitcher_vs_batter' : {
                Total.TOTAL : {
                    u'pct_single': 0.5,
                    u'pct_strikeout': 0.5,
                }
            },
            'reliever_vs_batter' : {
                Total.TOTAL : {
                    u'pct_strikeout': 1.0,
                }
            }
        }

        results = self.__runGame(weights, b_h, b_a, p_h, p_a, False)
        self.assertEqual(
            [
                results['home_win_pct'],
                results['weights_i'],
                results['weights'],
            ],
            [
                1.0,
                7,
                'p_total_100',
            ]
        )

if __name__ == '__main__':
    unittest.main()
