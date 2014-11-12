from Constants import *
from WeightsMutator import WeightsMutator

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

        # Used to speed up getBatterStats.
        self.storedBatterStats = {}

    def getBatterStats(self, batter, inning, outs, bases, winning):
        index = str(batter) + str(outs) + str(bases)
        if self.weightsMutator:
            self.__setCategoryWeights(inning, outs, bases, winning)

        # If no mutator, can use self.storedBatterStats to speed up this step.
        else:
            if index in self.storedBatterStats:
                return (self.storedBatterStats[index]['stacked'],
                    self.storedBatterStats[index]['unstacked'])

        stat_weights = self.__getStatWeights(inning, outs, bases)
        batter_stats = self.battingData[str(batter)]
        stats_to_average = {}
        for stat,weight in stat_weights.iteritems():
            if stat in batter_stats:
                stats_to_average[stat] = batter_stats[stat]
            else:
                stats_to_average[stat] = self.pitcherData[stat]

        weighted_batter_stats = self.__calculateWeightedBatterStats(
            stat_weights,
            stats_to_average
        )

        stacked = self.__calculateStackedBatterStats(weighted_batter_stats)

        if not self.weightsMutator:
            self.storedBatterStats[index] = {
                'stacked' : stacked,
                'unstacked' : weighted_batter_stats
            }

        # Return weighted_batter_stats for logging.
        return (stacked, weighted_batter_stats)

    def __calculateStackedBatterStats(self, weighted_batter_stats):
        stacked_batter_stats = {}
        for stat,value in weighted_batter_stats.iteritems():
            if not stacked_batter_stats:
               stacked_value = value
            else:
                stacked_value = (max(stacked_batter_stats.values()) + value)
            stacked_batter_stats[stat.replace('pct_', '')] = stacked_value

        return stacked_batter_stats

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

        return weighted_batter_stats



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
