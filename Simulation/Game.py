from Constants import *
from WeightsMutator import WeightsMutator
from Team import Team
import random, json

class Game:

    __HOME = 'Home'
    __AWAY = 'Away'

    def __init__(self, weights, input_data, at_bat_impact_data):
        self.atBatImpactData = at_bat_impact_data
        self.gameID = input_data['gameid']
        self.teams = {
            self.__HOME :
                 Team(
                    HomeAway.HOME,
                    weights,
                    input_data['pitching_a'],
                    input_data['batting_h'],
                ),
            self.__AWAY :
                Team(
                    HomeAway.AWAY,
                    weights,
                    input_data['pitching_h'],
                    input_data['batting_a'],
                )
        }

    def playGame(self):
        self.__initializeResults()

        # Logging only occurs if set.
        self.log = []

        # Set starting state of game.
        self.inning = 1
        self.batter = {self.__HOME : 1, self.__AWAY : 1}
        self.score = {self.__HOME : 0, self.__AWAY : 0}

        while True:
            self.__playInning(self.__AWAY)

            # End game before bottom of inning if >= 9 and home winning.
            if (self.inning >= 9 and
                self.score[self.__HOME] > self.score[self.__AWAY]):
                break

            self.__playInning(self.__HOME)

            # End game if inning >= 9 and not tied.
            if (self.inning >= 9 and
                self.score[self.__HOME] is not self.score[self.__AWAY]):
                break

            self.inning += 1

        self.results[self.__HOME]['runs'] = self.score[self.__HOME]
        self.results[self.__AWAY]['runs'] = self.score[self.__AWAY]
        return self.results, self.log


    def __playInning(self, team):
        # Set starting state of inning.
        self.outs = 0
        self.bases = Bases.EMPTY

        while True:
            self.__playAtBat(team)
            if self.outs > 2:
                break


    def __playAtBat(self, team):
        winning = (True if self.score[team] > sum(self.score.values()) / 2.0
            else False)
        batter_stats, unstacked_batter_stats = self.teams[team].getBatterStats(
            self.batter[team],
            self.inning,
            self.outs,
            self.bases,
            winning
        )
        # TODO(smas): Add function to calculate whether steal occurs during
        # at bat.
        hit_type = self.__getRandomlyGeneratedResult(batter_stats)
        stacked_hit_stats = self.__processAtBatImpact(hit_type, team)
        self.results[team][hit_type] += 1

        if self.loggingOn is True:
            self.__addToLog(
                team,
                hit_type,
                unstacked_batter_stats,
                stacked_hit_stats
            )

        self.batter[team] = (self.batter[team] + 1
            if self.batter[team] + 1 <= 9
            else 1
        )

    def __processAtBatImpact(self, hit_type, team):
        index = '%d%d%s' % (self.outs, self.bases, hit_type)
        stacked_atbat_impact_stats = self.atBatImpactData[index]
        hit_impact = self.__getRandomlyGeneratedResult(
            stacked_atbat_impact_stats
        )
        self.outs, self.bases, runs = map(int, hit_impact.split('_'))
        self.score[team] += runs

        # Return for logging.
        return stacked_atbat_impact_stats

    def __getRandomlyGeneratedResult(self, stats):
        # Get hit type by subtracting rand from all stacked_values and getting
        # smallest result that's still > 0. This means the stacked value that is
        # closest but greater than rand will be selected.

        # Due to rounding, top value is not always 1.0, so need to keep doing
        # this until rand <= top value.
        while (True):
            rand = random.random()
            filtered_stats = {}
            for stat,value in stats.iteritems():
                if value - rand >= 0:
                    filtered_stats[stat] = value - rand
            if filtered_stats:
                break

        return min(filtered_stats, key=filtered_stats.get)

    def __addToLog(self,
        team,
        hit_type,
        unstacked_batter_stats,
        stacked_hit_stats):
        self.log.append([
            json.dumps(self.score.copy()),
            self.inning,
            team,
            self.outs,
            self.batter[team],
            hit_type,
            self.bases,
            json.dumps(unstacked_batter_stats),
            json.dumps(stacked_hit_stats),
            self.gameID
        ])

    def __initializeResults(self):
        events = {
            'runs' : 0,
            'single' : 0,
            'double' : 0,
            'triple' : 0,
            'home_run' : 0,
            'walk' : 0,
            'strikeout' : 0,
            'ground_out' : 0,
            'fly_out' : 0
        }
        self.results = {
            self.__HOME : events.copy(),
            self.__AWAY : events.copy()
        }

    ########## SETTERS ##########

    def setWeightsMutator(self, weights_mutator):
        self.teams[self.__HOME].setWeightsMutator(weights_mutator)
        self.teams[self.__AWAY].setWeightsMutator(weights_mutator)

    def setLogging(self, log):
        self.loggingOn = log

    def setUseReliever(self, use_reliever):
        self.teams[self.__HOME].setUseReliever(use_reliever)
        self.teams[self.__AWAY].setUseReliever(use_reliever)
