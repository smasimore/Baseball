from Constants import *
from WeightsMutator import WeightsMutator

class Game:

    def __init__(self, weights, input_data):
        self.homeTeam = Team(
            HomeAway.HOME,
            weights,
            input_data['pitching_a'],
            input_data['batting_h'],
        )
        self.awayTeam = Team(
            HomeAway.AWAY,
            weights,
            input_data['pitching_h'],
            input_data['batting_a'],
        )

    def setWeightsMutator(self, weights_mutator):
        self.homeTeam.setWeightsMutator(weights_mutator)
        self.awayTeam.setWeightsMutator(weights_mutator)

    def run(self):
        self.homeTeam.getBatterStats('', 0, 0, Bases.EMPTY)
        self.awayTeam.getBatterStats('', 0, 0, Bases.EMPTY)

class Team:

    def __init__(self,
        home_away,
        weights,
        pitching_data,
        batting_data,
    ):
        self.homeAway = home_away
        self.categoryWeights = weights
        self.pitcherData = pitching_data
        self.battingData = batting_data

    def getBatterStats(self, batter, inning, outs, bases):
        self.__setCategoryWeights(1, 0, Bases.EMPTY, True)
        stat_weights = self.__getStatWeights(1, 0, Bases.EMPTY)

        print stat_weights



    ########## SETTERS ##########

    def __setCategoryWeights(self, inning, outs, bases, winning):
        if self.weightsMutator:
            method = getattr(WeightsMutator(), self.weightsMutator)
            self.categoryWeights = method(inning, outs, bases, winning)

    def setWeightsMutator(self, weights_mutator):
        self.weightsMutator = weights_mutator



    ########## GETTERS ##########

    # Dependent on inning, outs, and bases.
    def __getStatWeights(self, inning, outs, bases):
        # Reset statWeights for each batter.
        stat_weights = {}

        if StatCategories.TOTAL in self.categoryWeights.keys():
            stat_weights[Total.TOTAL] = self.categoryWeights[
                StatCategories.TOTAL
            ]

        if StatCategories.HOME_AWAY in self.categoryWeights.keys():
            stat_weights[self.homeAway] = self.categoryWeights[
                StatCategories.HOME_AWAY
            ]

        if StatCategories.PITCHER_HANDEDNESS in self.categoryWeights.keys():
            stat_weights[self.__getPitcherHandedness()] = self.categoryWeights[
                StatCategories.PITCHER_HANDEDNESS
            ]

        if StatCategories.PITCHER_ERA_BAND in self.categoryWeights.keys():
            if inning <= self.pitcherData['innings']:
                stat_weights[self.__getPitcherERABand()] = (
                    self.categoryWeights[StatCategories.PITCHER_ERA_BAND]
                )
            else:
                stat_weights[self.__getRelieverERABand()] = (
                    self.categoryWeights[StatCategories.PITCHER_ERA_BAND]
                )

        if StatCategories.PITCHER_VS_BATTER in self.categoryWeights.keys():
            if inning <= self.pitcherData['innings']:
                stat_weights[PitcherVSBatter.PITCHER_VS_BATTER] = (
                    self.categoryWeights[StatCategories.PITCHER_VS_BATTER]
                )
            else:
                stat_weights[PitcherVSBatter.RELIEVER_VS_BATTER] = (
                    self.categoryWeights[StatCategories.PITCHER_VS_BATTER]
                )

        if StatCategories.SITUATION in self.categoryWeights.keys():
            stat_weights[self.__getSituation(bases, outs)] = (
                self.categoryWeights[StatCategories.SITUATION]
            )

        if StatCategories.STADIUM in self.categoryWeights.keys():
            stat_weights[Stadium.STADIUM] = (
                self.categoryWeights[StatCategories.STADIUM]
            )

        return stat_weights

    # Defaults to RIGHT if not specified.
    def __getPitcherHandedness(self):
        return (
            PitcherHandedness.LEFT
            if self.pitcherData['handedness'] == 'L'
            else PitcherHandedness.RIGHT
        )

    def __getPitcherERABand(self):
        return self.pitcherData['bucket']

    def __getRelieverERABand(self):
        return self.pitcherData['reliever_bucket']

    def __getSituation(self, bases, outs):
        # Check if 2 outs and batter in scoring position.
        if outs == 2 and bases > Bases.FIRST:
            return Situations.SCORING_POS_2O

        return Bases.BASES_TO_SITUATION[bases]
