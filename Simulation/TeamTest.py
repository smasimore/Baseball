import unittest
from Constants import *
from WeightsMutator import WeightsMutator
from Team import Team

class TeamTestCase(unittest.TestCase):

    __PITCHING_DATA = {
        u'avg_innings': 5,
        u'name': u'Dan Certner',
        u'reliever_era': 5.5,
        u'handedness': u'R',
        u'era': 3.5,
        u'reliever_vs_batter':{},
        u'pitcher_vs_batter': {}
    }

    __BATTING_DATA = {
        u'1': {
            u'BasesLoaded': {
                u'pct_fly_out': 0.2,
                u'pct_triple': 0.0,
                u'player_name': u'smas1',
                u'pct_ground_out': 0.1,
                u'pct_walk': 0.1,
                u'pct_double': 0.1,
                u'pct_strikeout': 0.1,
                u'pct_single': 0.3,
                u'pct_home_run': 0.1
            },
            u'100': {
                u'pct_fly_out': 0.1,
                u'pct_triple': 0.1,
                u'player_name': u'smas1',
                u'pct_ground_out': 0.1,
                u'pct_walk': 0.1,
                u'pct_double': 0.1,
                u'pct_strikeout': 0.1,
                u'pct_single': 0.3,
                u'pct_home_run': 0.1
            },
            u'75': {
                u'pct_fly_out': 0.1,
                u'pct_triple': 0.1,
                u'player_name': u'smas1',
                u'pct_ground_out': 0.1,
                u'pct_walk': 0.1,
                u'pct_double': 0.1,
                u'pct_strikeout': 0.1,
                u'pct_single': 0.3,
                u'pct_home_run': 0.1
            },
            u'Stadium': {
                u'pct_fly_out': 0.1,
                u'pct_triple': 0.1,
                u'player_name': u'smas1',
                u'pct_ground_out': 0.1,
                u'pct_walk': 0.1,
                u'pct_double': 0.1,
                u'pct_strikeout': 0.1,
                u'pct_single': 0.3,
                u'pct_home_run': 0.1
            }, u'Home': {
                u'pct_fly_out': 0.1,
                u'pct_triple': 0.1,
                u'player_name': u'smas1',
                u'pct_ground_out': 0.1,
                u'pct_walk': 0.1,
                u'pct_double': 0.1,
                u'pct_strikeout': 0.1,
                u'pct_single': 0.3,
                u'pct_home_run': 0.1
            }, u'VsRight': {
                u'pct_fly_out': 0.1,
                u'pct_triple': 0.1,
                u'player_name': u'smas1',
                u'pct_ground_out': 0.1,
                u'pct_walk': 0.1,
                u'pct_double': 0.1,
                u'pct_strikeout': 0.1,
                u'pct_single': 0.3,
                u'pct_home_run': 0.1
            },
            u'Total': {
                u'pct_fly_out': 0.1,
                u'pct_triple': 0.1,
                u'player_name': u'smas1',
                u'pct_ground_out': 0.1,
                u'pct_walk': 0.1,
                u'pct_double': 0.1,
                u'pct_strikeout': 0.1,
                u'pct_single': 0.3,
                u'pct_home_run': 0.1
            }
        }
    }


    __WEIGHTS = {
        'b_home_away': 0.3,
        'b_pitcher_handedness': 0.2,
        'b_stadium': 0.1,
        'b_situation': 0.1,
        'b_total': 0.3,
    }

    def test_nomutator(self):
        team = Team(
            HomeAway.HOME,
            self.__WEIGHTS,
            self.__PITCHING_DATA,
            self.__BATTING_DATA
        )

        stacked, unstacked = team.getBatterStats(
            1,
            1,
            0,
            Bases.FIRST_SECOND_THIRD,
            True
        )

        self.assertGreater(min(stacked.values()), 0)
        self.assertAlmostEqual(max(stacked.values()), 1)
        self.assertAlmostEqual(sum(unstacked.values()), 1)

        self.assertEqual(
            unstacked,
            {
                u'pct_fly_out': 0.11000000000000001,
                u'pct_triple': 0.09,
                u'pct_ground_out': 0.1,
                u'pct_walk': 0.1,
                u'pct_double': 0.1,
                u'pct_strikeout': 0.1,
                u'pct_single': 0.30000000000000004,
                u'pct_home_run': 0.1
            }
        )

    def test_mutator(self):
        team = Team(
            HomeAway.HOME,
            self.__WEIGHTS,
            self.__PITCHING_DATA,
            self.__BATTING_DATA
        )
        team.setWeightsMutator('example')

        stacked, unstacked = team.getBatterStats(
            1,
            1,
            0,
            Bases.FIRST_SECOND_THIRD,
            True
        )

        self.assertGreater(min(stacked.values()), 0)
        self.assertAlmostEqual(max(stacked.values()), 1)
        self.assertAlmostEqual(sum(unstacked.values()), 1)

        self.assertEqual(
            unstacked,
            {
                u'pct_fly_out': 0.1,
                u'pct_triple': 0.1,
                u'pct_ground_out': 0.1,
                u'pct_walk': 0.1,
                u'pct_double': 0.1,
                u'pct_strikeout': 0.1,
                u'pct_single': 0.3,
                u'pct_home_run': 0.1
            }
        )


if __name__ == '__main__':
    unittest.main()
