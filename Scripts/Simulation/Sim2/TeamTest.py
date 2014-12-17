import unittest
from Constants import *
from WeightsMutator import WeightsMutator
from Team import Team

class TeamTestCase(unittest.TestCase):

    __PITCHING_DATA = {
        u'avg_innings': 5,
        u'name': u'Dan Certner',
        u'reliever_era': 5.5,
        u'reliever_bucket': u'ERA100',
        u'handedness': u'R',
        u'bucket': u'ERA75',
        u'era': 3.5,
        u'reliever_vs_batter':{
            u'pct_fly_out': 0.1,
            u'pct_triple': 0.1,
            u'player_name': u'cert pitcher',
            u'pct_ground_out': 0.1,
            u'pct_walk': 0.1,
            u'pct_double': 0.1,
            u'pct_strikeout': 0.1,
            u'pct_single': 0.3,
            u'pct_home_run': 0.1
        },
        u'pitcher_vs_batter': {
            u'pct_fly_out': 0.1,
            u'pct_triple': 0.1,
            u'player_name': u'cert pitcher',
            u'pct_ground_out': 0.1,
            u'pct_walk': 0.1,
            u'pct_double': 0.1,
            u'pct_strikeout': 0.1,
            u'pct_single': 0.3,
            u'pct_home_run': 0.1
        }
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
            u'ERA100': {
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
            u'ERA75': {
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
        'pitcher_era_band': 0.1,
        'home_away': 0.2,
        'pitcher_handedness': 0.2,
        'stadium': 0.1,
        'situation': 0.1,
        'total': 0.1,
        'pitcher_vs_batter': 0.2
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
                u'pct_fly_out': 0.11000000000000003,
                u'pct_triple': 0.09000000000000001,
                u'pct_ground_out': 0.10000000000000002,
                u'pct_walk': 0.10000000000000002,
                u'pct_double': 0.10000000000000002,
                u'pct_strikeout': 0.10000000000000002,
                u'pct_single': 0.3,
                u'pct_home_run': 0.10000000000000002
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
        print unstacked
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
