class StatCategories:
    TOTAL = 'total'
    HOME_AWAY = 'home_away'
    PITCHER_HANDEDNESS = 'pitcher_handedness'
    PITCHER_ERA_BAND = 'pitcher_era_band'
    PITCHER_VS_BATTER = 'pitcher_vs_batter'
    SITUATION = 'situation'
    STADIUM = 'stadium'

class Total:
    TOTAL = 'Total'

class HomeAway:
    HOME = 'Home'
    AWAY = 'Away'

class PitcherHandedness:
    LEFT = 'VsLeft'
    RIGHT = 'VsRight'

class PitcherERABand:
    ERA25 = 'ERA25'
    ERA50 = 'ERA50'
    ERA75 = 'ERA75'
    ERA100 = 'ERA100'

class PitcherVSBatter:
    PITCHER_VS_BATTER = 'pitcher_vs_batter'
    RELIEVER_VS_BATTER = 'reliever_vs_batter'

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
