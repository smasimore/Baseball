class Stats:
    TOTAL = 'total'
    HOME_AWAY = 'home_away'
    PITCHER_HANDEDNESS = 'pitcher_handedness'
    PITCHER_ERA_BAND = 'pitcher_era_band'
    PITCHER_VS_BATTER = 'pitcher_vs_batter'
    SITUATION = 'situation'

class Bases:
    EMPTY = 0
    FIRST = 1
    SECOND = 2
    THIRD = 3
    FIRST_SECOND = 4
    FIRST_THIRD = 5
    SECOND_THIRD = 6
    FIRST_SECOND_THIRD = 7

class Situation:
    NONE_ON = 'NoneOn'
    RUNNERS_ON = 'RunnersOn'
    SCORING_POS = 'ScoringPos'
    SCORING_POS_2O = 'ScoringPos2Out'
    BASES_LOADED = 'BasesLoaded'

class Maps:

    # Doesn't include 2 out mapping. That is handled in
    # basesAndOutsToSituation.
    __BASES_TO_SITUATION = {
        Bases.EMPTY : Situation.NONE_ON,
        Bases.FIRST : Situation.RUNNERS_ON,
        Bases.SECOND : Situation.SCORING_POS,
        Bases.THIRD : Situation.SCORING_POS,
        Bases.FIRST_SECOND : Situation.SCORING_POS,
        Bases.FIRST_THIRD : Situation.SCORING_POS,
        Bases.SECOND_THIRD : Situation.SCORING_POS,
        Bases.FIRST_SECOND_THIRD : Situation.BASES_LOADED
    }

    @staticmethod
    def basesAndOutsToSituation(bases, outs):
        # Check if 2 outs and batter in scoring position.
        if outs == 2 and bases > Bases.FIRST:
            return Situation.SCORING_POS_2O

        return Maps.__BASES_TO_SITUATION[bases]

class Game:

    def __init__(self, weights, input_data):
        print 'You are alive'
        # Set up weights, team class, player class

