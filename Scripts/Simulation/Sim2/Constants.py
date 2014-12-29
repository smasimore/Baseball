# B prefix means it's the batter's stats. P means pitcher's.
class StatCategories:
    B_TOTAL = 'b_total'
    B_HOME_AWAY = 'b_home_away'
    B_PITCHER_HANDEDNESS = 'b_pitcher_handedness'
    B_PITCHER_ERA_BAND = 'b_pitcher_era_band'
    B_SITUATION = 'b_situation'
    B_STADIUM = 'b_stadium'

    P_TOTAL = 'p_total'
    P_HOME_AWAY = 'p_home_away'
    P_BATTER_HANDEDNESS = 'p_batter_handedness'
    P_BATTER_AVG_BAND = 'p_batter_avg_band'
    P_SITUATION = 'p_situation'
    P_STADIUM = 'p_stadium'

# Pitcher stats can be starter or reliever. These are the keys that contain
# the relevant pitcher's data within the sim_input['pitching_h'] dict.
class Pitcher:
    STARTER = 'pitcher_vs_batter'
    RELIEVER = 'reliever_vs_batter'

class Total:
    TOTAL = 'Total'

class HomeAway:
    HOME = 'Home'
    AWAY = 'Away'

    @staticmethod
    def getOpposite(ha):
        return HomeAway.HOME if ha == HomeAway.AWAY else HomeAway.AWAY

class Handedness:
    LEFT = 'VsLeft'
    RIGHT = 'VsRight'

    @staticmethod
    def getOpposite(h):
        return Handedness.RIGHT if h == Handedness.LEFT else Handedness.LEFT

# Lower means lower ERA/average. B stands for band.
class PerformanceBand:
    B25 = '25'
    B50 = '50'
    B75 = '75'
    B100 = '100'

class Situations:
    NONE_ON = 'NoneOn'
    RUNNERS_ON = 'RunnersOn'
    SCORING_POS = 'ScoringPos'
    SCORING_POS_2O = 'ScoringPos2Out'
    BASES_LOADED = 'BasesLoaded'

class Stadium:
    STADIUM = 'Stadium'

class Bases:
    EMPTY = 0
    FIRST = 1
    SECOND = 2
    THIRD = 3
    FIRST_SECOND = 4
    FIRST_THIRD = 5
    SECOND_THIRD = 6
    FIRST_SECOND_THIRD = 7

    # Doesn't include 2 out mapping. That is handled in
    # Team.getSituation().
    BASES_TO_SITUATION = {
        EMPTY : Situations.NONE_ON,
        FIRST : Situations.RUNNERS_ON,
        SECOND : Situations.SCORING_POS,
        THIRD : Situations.SCORING_POS,
        FIRST_SECOND : Situations.SCORING_POS,
        FIRST_THIRD : Situations.SCORING_POS,
        SECOND_THIRD : Situations.SCORING_POS,
        FIRST_SECOND_THIRD : Situations.BASES_LOADED
    }
