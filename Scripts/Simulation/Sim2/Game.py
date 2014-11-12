from Constants import *
from WeightsMutator import WeightsMutator
from Team import Team
import random, json

class Game:

    HOME = 'Home'
    AWAY = 'Away'

    def __init__(self, weights, input_data, at_bat_impact_data):
        self.atBatImpactData = at_bat_impact_data
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
        self.__initializeResults()

        # Logging only occurs if set.
        self.log = []

        # Set starting state of game.
        self.inning = 1
        self.batter = {self.HOME : 1, self.AWAY : 1}
        self.score = {self.HOME : 0, self.AWAY : 0}

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

        self.results[self.HOME]['runs'] = self.score[self.HOME]
        self.results[self.AWAY]['runs'] = self.score[self.AWAY]
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
        hit_impact = self.__getRandomlyGeneratedResult(
            self.atBatImpactData[index
        ])
        self.outs, self.bases, runs = map(int, hit_impact.split('_'))
        self.score[team] += runs

        # Return for logging.
        return self.atBatImpactData[index]

    def __getRandomlyGeneratedResult(self, stats):
        # Get hit type by subtracting rand from all stacked_values and getting
        # smallest result that's still > 0. This means the stacked value that is
        # closest but greater than rand will be selected.
        rand = random.random()
        filtered_stats = {}
        for stat,value in stats.iteritems():
            if value - rand >= 0:
                filtered_stats[stat] = value - rand

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
            json.dumps(stacked_hit_stats)
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
        self.results = {self.HOME : events.copy(), self.AWAY : events.copy()}

    ########## SETTERS ##########

    def setWeightsMutator(self, weights_mutator):
        self.teams[self.HOME].setWeightsMutator(weights_mutator)
        self.teams[self.AWAY].setWeightsMutator(weights_mutator)

    def setLogging(self, log):
        self.loggingOn = log
