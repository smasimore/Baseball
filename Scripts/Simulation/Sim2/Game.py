from Constants import *
from WeightsMutator import WeightsMutator

class Game:

    HOME = 'Home'
    AWAY = 'Away'

    def __init__(self, weights, input_data):
        self.teams = {
            self.HOME :
                 Team(
                    HomeAway.HOME,
                    weights,
                    input_data['pitching_a'],
                    input_data['batting_h'],
                ),
            self.AWAY :
                Team(
                    HomeAway.AWAY,
                    weights,
                    input_data['pitching_h'],
                    input_data['batting_a'],
                )
        }

    def playGame(self):
        # Logging only occurs if set.
        self.log = []

        # Set starting state of game.
        # TESTING - set 1 for real
        self.inning = 9
        self.batter = {self.HOME : 1, self.AWAY : 1}
        # TESTING - set 0 - 0 for real
        self.score = {self.HOME : 1, self.AWAY : 0}

        while True:
            self.__playInning(self.AWAY)

            # End game before bottom of inning if >= 9 and home winning.
            if (self.inning >= 9 and
                self.score[self.HOME] > self.score[self.AWAY]):
                break

            self.__playInning(self.HOME)

            # End game if inning >= 9 and not tied.
            if (self.inning >= 9 and
                self.score[self.HOME] is not self.score[self.AWAY]):
                break

            self.inning += 1

        # TESTING
        print self.log


    def __playInning(self, team):
        # Set starting state of inning.
        self.outs = 0
        self.bases = Bases.EMPTY

        while True:
            self.__playAtBat(team)

            # TESTING
            self.outs = 3

            if self.outs >= 3:
                break


    def __playAtBat(self, team):
        batter_stats = self.teams[team].getBatterStats(
            self.batter[team],
            self.inning,
            self.outs,
            self.bases
        )

        if self.loggingOn is True:
            self.__addToLog(team)


    def __addToLog(self, team):
        self.log.append([
            self.score,
            self.inning,
            team,
            self.outs,
            self.batter[team]
        ])

    ########## SETTERS ##########

    def setWeightsMutator(self, weights_mutator):
        self.teams[self.HOME].setWeightsMutator(weights_mutator)
        self.teams[self.AWAY].setWeightsMutator(weights_mutator)

    def setLogging(self, log):
        self.loggingOn = log



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
        batter_stats = self.battingData[str(batter)]

        stats_to_average = {}
        for stat, weight in stat_weights.iteritems():
            if stat in batter_stats:
                stats_to_average[stat] = batter_stats[stat]
            else:
                stats_to_average[stat] = self.pitcherData[stat]

        return self.__calculateWeightedBatterStats(
            stat_weights,
            stats_to_average
        )


    def __calculateWeightedBatterStats(self, stat_weights, stats_to_average):
        weighted_batter_stats = {}
        # Loop through each type of at bat result.
        for at_bat_result in stats_to_average[stats_to_average.keys()[0]]:
            if at_bat_result == 'player_name':
                continue

            weighted_batter_stats[at_bat_result] = 0

            for stat in stats_to_average:
                # Add weighted stat value to at_bat_result.
                weighted_batter_stats[at_bat_result] += (
                    stat_weights[stat] * stats_to_average[stat][at_bat_result]
                )

        # TESTING
        print stat_weights
        print stats_to_average
        print weighted_batter_stats



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
