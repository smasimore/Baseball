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
        self.pitchingData = pitching_data
        self.battingData = batting_data

        # Initialize.
        self.weightsMutator = None
        self.useReliever = True

        # Used to speed up getBatterStats.
        self.storedBatterStats = {}

    def getBatterStats(self, batter, inning, outs, bases, winning):
        pitcher_type = self.__getPitcherType(inning)
        index = str(batter) + str(outs) + str(bases) + pitcher_type
        if self.weightsMutator:
            self.__setCategoryWeights(inning, outs, bases, winning)

        # If no mutator, can use self.storedBatterStats to speed up this step.
        else:
            if index in self.storedBatterStats:
                return (self.storedBatterStats[index]['stacked'],
                    self.storedBatterStats[index]['unstacked'])

        stat_weights = self.__getStatWeights(
            inning,
            outs,
            bases,
            batter
        )
        batter_stats = self.battingData[str(batter)]

        stats_to_average = {}
        for stat,weight in stat_weights.iteritems():
            # Batter stats.
            if stat[:2] != 'p_':
                stats_to_average[stat] = batter_stats[stat]
            # Pitcher stats.
            else:
                # Remove 'p_' prefix to get pitcher stats.
                p_stat = stat[2:]
                stats_to_average[stat] = self.pitchingData[pitcher_type][p_stat]

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
                # Needs to be forced to be 0 or else can get multiple values
                # equal to > 0 values (e.g. single and strikeout = 1)
                if value == 0:
                    stacked_value = 0
                else:
                    stacked_value = (max(stacked_batter_stats.values()) + value)
            stacked_batter_stats[stat.replace('pct_', '')] = stacked_value

        return stacked_batter_stats

    def __calculateWeightedBatterStats(self, stat_weights, stats_to_average):
        weighted_batter_stats = {}
        # Loop through each type of at bat result.
        for at_bat_result in stats_to_average[stats_to_average.keys()[0]]:
            if at_bat_result[:4] != 'pct_':
                continue

            weighted_batter_stats[at_bat_result] = 0

            for stat in stats_to_average:
                # Add weighted stat value to at_bat_result.
                weighted_batter_stats[at_bat_result] += (
                    stat_weights[stat] *
                    float(stats_to_average[stat][at_bat_result])
                )

        return weighted_batter_stats



    ########## SETTERS ##########

    def __setCategoryWeights(self, inning, outs, bases, winning):
        if self.weightsMutator:
            method = getattr(WeightsMutator(), self.weightsMutator)
            self.categoryWeights = method(inning, outs, bases, winning)

    def setWeightsMutator(self, weights_mutator):
        self.weightsMutator = weights_mutator

    def setUseReliever(self, use_reliever):
        self.useReliever = use_reliever



    ########## GETTERS ##########

    # Dependent on inning, outs, and bases.
    def __getStatWeights(self, inning, outs, bases, batter):
        # Reset statWeights for each batter.
        stat_weights = {}
        if StatCategories.B_TOTAL in self.categoryWeights.keys():
            stat_weights[Total.TOTAL] = self.categoryWeights[
                StatCategories.B_TOTAL
            ]

        if StatCategories.B_HOME_AWAY in self.categoryWeights.keys():
            stat_weights[self.homeAway] = self.categoryWeights[
                StatCategories.B_HOME_AWAY
            ]

        if StatCategories.B_PITCHER_HANDEDNESS in self.categoryWeights.keys():
            stat_weights[self.__getPitcherHandedness()] = self.categoryWeights[
                StatCategories.B_PITCHER_HANDEDNESS
            ]

        if StatCategories.B_SITUATION in self.categoryWeights.keys():
            stat_weights[self.__getSituation(bases, outs)] = (
                self.categoryWeights[StatCategories.B_SITUATION]
            )

        if StatCategories.B_STADIUM in self.categoryWeights.keys():
            stat_weights[Stadium.STADIUM] = (
                self.categoryWeights[StatCategories.B_STADIUM]
            )

        if StatCategories.P_TOTAL in self.categoryWeights.keys():
            stat_weights['p_' + Total.TOTAL] = self.categoryWeights[
                StatCategories.P_TOTAL
            ]

        if StatCategories.P_HOME_AWAY in self.categoryWeights.keys():
            stat_weights['p_' + HomeAway.getOpposite(self.homeAway)] = (
                self.categoryWeights[StatCategories.P_HOME_AWAY]
            )

        if StatCategories.P_BATTER_HANDEDNESS in self.categoryWeights.keys():
            stat_weights['p_' + self.__getBatterHandedness(batter)] = (
                self.categoryWeights[StatCategories.P_BATTER_HANDEDNESS]
            )

        if StatCategories.P_SITUATION in self.categoryWeights.keys():
            stat_weights['p_' + self.__getSituation(bases, outs)] = (
                self.categoryWeights[StatCategories.P_SITUATION]
            )

        if StatCategories.P_STADIUM in self.categoryWeights.keys():
            stat_weights['p_' + Stadium.STADIUM] = (
                self.categoryWeights[StatCategories.P_STADIUM]
            )

        return stat_weights

    # Defaults to RIGHT if not specified.
    def __getPitcherHandedness(self):
        return (
            Handedness.LEFT
            if self.pitchingData['handedness'] == 'L'
            else Handedness.RIGHT
        )

    def __getBatterHandedness(self, batter):
        batter_h = self.battingData[str(batter)]['handedness']

        # Switch hitter. Default to opposite of pitcher's handedness.
        if batter_h == 'B':
            pitcher_h = self.__getPitcherHandedness()
            return Handedness.getOpposite(pitcher_h)
        else:
            return (
                Handedness.LEFT
                if batter_h == 'L' else Handedness.RIGHT
            )

    def __getSituation(self, bases, outs):
        # Check if 2 outs and batter in scoring position.
        if outs == 2 and bases > Bases.FIRST:
            return Situations.SCORING_POS_2O

        return Bases.BASES_TO_SITUATION[bases]

    def __getPitcherType(self, inning):
        if (not self.useReliever or
            inning <= self.pitchingData['avg_innings'] or
            Pitcher.RELIEVER not in self.pitchingData or
            not self.pitchingData[Pitcher.RELIEVER]):
            return Pitcher.STARTER

        return Pitcher.RELIEVER
