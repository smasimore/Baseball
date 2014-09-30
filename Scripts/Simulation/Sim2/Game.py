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
    HOME = 'Home'

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

class Game:

    def __init__(self, weights, input_data):
        self.homeTeam = Team(
            HomeAway.HOME,
            weights,
            input_data['pitching_a'],
            input_data['batting_h']
        )
        self.awayTeam = Team(
            HomeAway.AWAY,
            weights,
            input_data['pitching_h'],
            input_data['batting_a']
        )

class Team:

    def __init__(self, home_away, weights, pitching_data, batting_data):
        self.homeAway = home_away
        self.categoryWeights = weights
        self.pitcherData = pitching_data
        self.battingData = batting_data

        self.setStatWeights(0, 0, Bases.EMPTY)
        print self.statWeights
        # set weights -- 2 vars, one mapping categories to weight, then need a temp var for each that maps category to type
        # each time a new batter comes up, have to recalculate batting stats


    # [NEXT] def getBatterStats(self, batter, inning, outs, bases)

    ########## SETTERS ##########

    # Dependent on inning, outs, and bases.
    def setStatWeights(self, inning, outs, bases):
        # Reset statWeights for each batter.
        self.statWeights = {}

        if StatCategories.TOTAL in self.categoryWeights.keys():
            self.statWeights[Total.TOTAL] = self.categoryWeights[
                StatCategories.TOTAL
            ]

        if StatCategories.HOME_AWAY in self.categoryWeights.keys():
            self.statWeights[self.homeAway] = self.categoryWeights[
                StatCategories.HOME_AWAY
            ]

        # [QUESTION] For inning > the starting pitcher's inning, what do we want
        # to do with this?
        if StatCategories.PITCHER_HANDEDNESS in self.categoryWeights.keys():
            self.statWeights[self.getPitcherHandedness()] = self.categoryWeights[
                StatCategories.PITCHER_HANDEDNESS
            ]

        if StatCategories.PITCHER_ERA_BAND in self.categoryWeights.keys():
            if inning <= self.pitcherData['innings']:
                self.statWeights[self.getPitcherERABand()] = (
                    self.categoryWeights[StatCategories.PITCHER_ERA_BAND]
                )
            else:
                self.statWeights[self.getRelieverERABand()] = (
                    self.categoryWeights[StatCategories.PITCHER_ERA_BAND]
                )

        if StatCategories.PITCHER_VS_BATTER in self.categoryWeights.keys():
            if inning <= self.pitcherData['innings']:
                self.statWeights[PitcherVSBatter.PITCHER_VS_BATTER] = (
                    self.categoryWeights[StatCategories.PITCHER_VS_BATTER]
                )
            else:
                self.statWeights[PitcherVSBatter.RELIEVER_VS_BATTER] = (
                    self.categoryWeights[StatCategories.PITCHER_VS_BATTER]
                )

        if StatCategories.SITUATION in self.categoryWeights.keys():
            self.statWeights[self.getSituation(bases, outs)] = (
                self.categoryWeights[StatCategories.SITUATION]
            )

        if StatCategories.STADIUM in self.categoryWeights.keys():
            self.statWeights[self.getStadium()] = (
                self.categoryWeights[StatCategories.STADIUM]
            )



    ########## GETTERS ##########

    # Defaults to RIGHT if not specified.
    def getPitcherHandedness(self):
        return (
            PitcherHandedness.LEFT
            if self.pitcherData['handedness'] == 'L'
            else PitcherHandedness.RIGHT
        )

    def getPitcherERABand(self):
        return self.pitcherData['bucket']

    def getRelieverERABand(self):
        return self.pitcherData['reliever_bucket']

    def getSituation(self, bases, outs):
        # Check if 2 outs and batter in scoring position.
        if outs == 2 and bases > Bases.FIRST:
            return Situations.SCORING_POS_2O

        return Bases.BASES_TO_SITUATION[bases]

    def getStadium(self):
        return (
            Stadium.HOME
            if self.homeAway == HomeAway.HOME
            else Stadium.STADIUM
        )
