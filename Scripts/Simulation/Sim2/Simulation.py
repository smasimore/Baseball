import MySQL, time, datetime, json
from Constants import *
from Game import Game
from WeightsMutator import WeightsMutator
from multiprocessing import Process, Manager
from collections import OrderedDict

class Simulation:

    ########## PRIVATE CONSTANTS ##########
    __TABLE = 'sim_input'
    __TEST_TABLE = 'sim_input_test'
    __DEBUG_TABLE = 'sim_debug'
    __WEIGHTS_INDEX_TABLE = 'sim_weights_index'
    __AT_BAT_IMPACT_TABLE = 'at_bat_impact'
    __OUTPUT_TABLE = 'sim_output'
    __AT_BAT_IMPACT_INDEX = ['start_outs', 'start_bases', 'event_name']
    __THREADS = 1

    ########## COLUMN LISTS ##########
    SIM_OUTPUT_COLUMNS = [
        'home_win_pct',
        'game_details',
        'gameid',
        'game_date',
        'date_ran_sim',
        'home',
        'away',
        'season',
        'stats_year',
        'stats_type',
        'weights_i',
        'weights',
        'weights_mutator',
        'analysis_runs',
        'sim_game_date'
    ]

    DEBUG_COLUMNS = [
        'score',
        'inning',
        'team',
        'outs',
        'batter',
        'hit_type',
        'bases',
        'batter_stats',
        'stacked_hit_stats',
        'season',
        'stats_year',
        'stats_type',
        'weights_i',
        'weights',
        'weights_mutator',
        'analysis_runs',
        'sim_game_date',
        'test_run'
    ]

    ########## PARAM VALIDATION LISTS ##########
    STATS_TYPES = [
        'basic',
        'magic',
    ]

    STATS = [
        StatCategories.B_TOTAL,
        StatCategories.B_HOME_AWAY,
        StatCategories.B_PITCHER_HANDEDNESS,
        StatCategories.B_PITCHER_ERA_BAND,
        StatCategories.B_SITUATION,
        StatCategories.B_STADIUM,
        StatCategories.P_TOTAL,
        StatCategories.P_HOME_AWAY,
        StatCategories.P_BATTER_HANDEDNESS,
        StatCategories.P_BATTER_AVG_BAND,
        StatCategories.P_SITUATION,
        StatCategories.P_STADIUM,
    ]

    STATS_YEAR = [
        'career',
        'current',
        'previous',
    ]



    ########## DEFAULT PARAMS - use setter functions to override. ##########
    ANALYSIS_RUNS = 1
    GAME_DATE = None # Set a date to run a specific day of games.
                     # Timespan not currently available.
    TEST_RUN = False
    WEIGHTS_MUTATOR = None



    def __init__(self,
        weights,
        season = time.strftime("%Y"),
        stats_year = 'current',
        stats_type = 'basic'):

        self.startTime = datetime.datetime.now()

        self.validateWeights(weights, stats_type)
        self.validateSeasonYear(season)
        self.validateInList(stats_type, self.STATS_TYPES)
        self.validateInList(stats_year, self.STATS_YEAR)

        self.weights = weights
        self.season = season
        self.statsYear = stats_year
        self.statsType = stats_type
        self.inputData = []

        self.weightsMutator = self.WEIGHTS_MUTATOR
        self.debugLoggingOn = True

        # Extra, defaulted params. To change call setters.
        self.analysisRuns = self.ANALYSIS_RUNS
        self.gameDate = self.GAME_DATE
        self.testRun = self.TEST_RUN # Use this in conjunction with setting a
                                     # file with test data. Sim will use test
                                     # data rather than MySQL.


    def run(self):
        self.__addWeightsIndex()
        if not self.testRun:
            self.__fetchInputData()
        else:
            if not self.inputData:
                raise ValueError('When testing, inputData must be set manually')


        self.__fetchAtBatImpactData()
        self.__runGames()

        print 'Time Taken: ' + str(datetime.datetime.now() - self.startTime)

        # Return first row with cols for tests.
        return dict(zip(self.SIM_OUTPUT_COLUMNS, self.__exportResults()[0]))

    def __runGame(self, row_number, sim_results):
        game_data = self.inputData[row_number]
        game = Game(
            self.weights,
            game_data,
            self.atBatImpactData
        )

        # If any features are added, create a setter in Game and set here
        # instead of passing in an additional parameter.
        game.setWeightsMutator(self.weightsMutator)
        game.setLogging(self.debugLoggingOn)

        game_results = []
        for analysis_run in range(0, self.analysisRuns):
            time = datetime.datetime.now()
            run_results, debug_log = game.playGame()

            # Turn off debug logging so that only logs for 1 game per Sim run.
            if debug_log:
                self.debugLoggingOn = False
                self.__logToDebugTable(debug_log)
                game.setLogging(self.debugLoggingOn)

            # Adding to run_results which will need to be processed to get
            # game-level stats (e.g. % home win).
            game_results.append(run_results)

        # Get pct win and event averages.
        results = self.__processGameResults(game_results)
        results.extend([
            game_data['gameid'],
            game_data['game_date'],
            time.strftime("%Y-%m-%d"),
            game_data['home'],
            game_data['away']
        ])
        sim_results.append(results)


    # Multiprocessing method that calls runGame.
    def __runGames(self):
        # Create shared memory dict.
        manager = Manager()
        sim_results = manager.list()

        rows = len(self.inputData)
        rows_completed = 0
        while rows_completed < len(self.inputData):
            # For last run through, make sure you're not creating more threads
            # than necessary. E.g. if there's only 1 game, only have 1 thread.
            threads = (
                    self.__THREADS
                    if rows_completed + self.__THREADS < rows
                    else rows - rows_completed
            )
            processes = {}
            for t in range(threads):
                row_number = rows_completed + t
                processes[t] = Process(
                    target=self.__runGame,
                    args=(
                        row_number,
                        sim_results
                    )
                )
            for t in range(threads):
                processes[t].start()
            for t in range (threads):
                processes[t].join()
            rows_completed = rows_completed + threads

        self.simResults = sim_results

        # Print status.
        print str(rows_completed / len(self.inputData) * 100) + '% COMPLETE'

    def __addWeightsIndex(self):
        self.weightsIndex = 0
        while not self.weightsIndex:
            query = ("""SELECT * FROM %s""") % self.__WEIGHTS_INDEX_TABLE
            results = MySQL.read(query)
            weights_readable = self.__getReadableWeights()
            for row in results:
                if row['weights_readable'] == weights_readable:
                    self.weightsIndex = row['weights_index']

            if not self.weightsIndex:
                MySQL.insert(
                    self.__WEIGHTS_INDEX_TABLE,
                    ['weights_readable'],
                    [weights_readable]
                )

    def __fetchInputData(self):
        table = self.__TABLE if self.testRun is False else self.__TEST_TABLE
        query = (
            """SELECT *
            FROM %s
            WHERE
                stats_type = '%s' AND
                stats_year = '%s' AND
                season = %d"""
            % (
                table,
                self.statsType,
                self.statsYear,
                self.season
            )
        )

        # If gameDate set, only pull one day of data.
        if self.gameDate:
            query = query + " AND game_date = '%s'" % self.gameDate

        results = MySQL.read(query)
        if not results:
            raise ValueError(
                'No data in %s for query \n %s' % (self.__TABLE, query)
            )

        # Convert jsons to dicts.
        formatted_results = []
        for row in results:
            formatted_row = {}
            for key, value in row.items():
                if isinstance(value, str):
                    # Try/catch because value might not be json.
                    try:
                        formatted_row[key] = json.loads(value)
                    except ValueError, e:
                        formatted_row[key] = value
                else:
                    formatted_row[key] = value

            formatted_results.append(formatted_row)

        self.inputData = formatted_results

    def __fetchAtBatImpactData(self):
        query = (""" SELECT * FROM %s""" % (self.__AT_BAT_IMPACT_TABLE))
        results = MySQL.read(query)

        if not results:
            raise ValueError(
                'No data in %s for query \n %s' % (
                    self.__AT_BAT_IMPACT_TABLE,
                    query
                )
            )

        # Convert to useable data (index : {result1 : .1, result2 : .2, etc...}
        self.atBatImpactData = self.__processAtBatImpactData(results)

    def __processAtBatImpactData(self, impact_data):
        unstacked_stats = {}
        for row in impact_data:
            index = ''
            for item in self.__AT_BAT_IMPACT_INDEX:
                index = index + str(row[item])
            end_state = (str(row['end_outs']) + '_' + str(row['end_bases']) +
                '_' + str(row['runs_added']))

            if index not in unstacked_stats:
                unstacked_stats[index] = {}
            unstacked_stats[index][end_state] = row['rate']

        stacked_stats = {}
        for start_state, unstacked_stat in unstacked_stats.iteritems():
            stacked_stat = {}
            previous_impact_stat = 0
            for at_bat_impact in unstacked_stat.keys():
                stacked_stat[at_bat_impact] = (previous_impact_stat +
                    unstacked_stat[at_bat_impact])
                previous_impact_stat = stacked_stat[at_bat_impact]
            stacked_stats[start_state] = stacked_stat

        return stacked_stats


    def __logToDebugTable(self, debug_log):
        sim_params = self.__getSimParams()
        for at_bat in debug_log:
            at_bat.extend(sim_params)
            at_bat.append(self.testRun)

        MySQL.delete("DELETE FROM %s" % self.__DEBUG_TABLE)
        MySQL.insert(self.__DEBUG_TABLE, self.DEBUG_COLUMNS, debug_log)

    def __processGameResults(self, game_results):
        totals = {}
        home_wins = 0
        for run in game_results:
            if (run[HomeAway.HOME]['runs'] > run[HomeAway.AWAY]['runs']):
                home_wins += 1
            for team, stats in run.iteritems():
                if team not in totals.keys():
                    totals[team] = {}
                for event, num in stats.iteritems():
                    if event in totals[team].keys():
                        totals[team][event] += num
                    else:
                        totals[team][event] = num

        totals[HomeAway.HOME].update((event, float(num)/self.analysisRuns)
            for event, num in totals[HomeAway.HOME].items())
        totals[HomeAway.AWAY].update((event, float(num)/self.analysisRuns)
            for event, num in totals[HomeAway.AWAY].items())

        return [float(home_wins)/self.analysisRuns, json.dumps(totals)]

    def __getReadableWeights(self):
        ordered_weights = OrderedDict(sorted(self.weights.items()))
        # For easy reading, converting weights to percents.
        ordered_weights.update((weight, int(val*100))
            for weight, val in ordered_weights.items())

        readable = '__'.join([
            '_'.join([
                weight, str(val)
            ]) for weight, val in ordered_weights.items()
        ])

        return readable

    def __exportResults(self):
        sim_params = self.__getSimParams()
        results = []
        for game in self.simResults:
            game.extend(sim_params)
            results.append(game)

        if not self.testRun:
            MySQL.dropPartition(
                self.__OUTPUT_TABLE,
                str(self.season)+str(self.weightsIndex)
            )
            MySQL.addPartition(
                self.__OUTPUT_TABLE,
                str(self.season)+str(self.weightsIndex),
                str(self.season)+", "+str(self.weightsIndex)
            )
            MySQL.insert(
                self.__OUTPUT_TABLE,
                self.SIM_OUTPUT_COLUMNS,
                results
            )

        return results


    ########## EXTRA PARAM FUNCTIONS ##########

    def setAnalysisRuns(self, runs):
        self.validateAnalysisRuns(runs)
        self.analysisRuns = runs

    def setGameDate(self, date):
        self.validateDate(date)
        self.gameDate = date

    def setTestRun(self, test_run):
        self.validateTestRun(test_run)
        self.testRun = test_run

    def setWeightsMutator(self, weights_mutator):
        self.validateWeightsMutator(weights_mutator)
        self.weightsMutator = weights_mutator

    # Override inputData for tests.
    def setInputData(self, input_data):
        self.inputData = input_data

    def __getSimParams(self):
        weights_readable = self.__getReadableWeights()
        return [
            self.season,
            self.statsYear,
            self.statsType,
            self.weightsIndex,
            weights_readable,
            self.weightsMutator,
            self.analysisRuns,
            self.gameDate
        ]

    ########## PARAM VALIDATION FUNCTIONS ##########

    def validateWeights(self, weights, stats_type):
        # Check if param is dictionary.
        if type(weights) is not dict:
            raise ValueError('Weight param needs to be a dict. %s is not.'
                % weights)
        # Check if stats in STATS.
        for stat in weights:
            self.validateInList(stat, self.STATS)
            if type(weights[stat]) is not float:
                raise ValueError('Weight value needs to be a float. %s is not.'
                    % weights[stat])
        # If stats_type is basic, make sure weights add to 1.
        if stats_type == 'basic':
            # Rounding to 3 decimals due to Python float precision issues.
            if  round(sum(weights.values()), 3) != 1:
                raise ValueError(
                    "Weights must add to 1 (not %s) for basic stats_type."
                    % sum(weights.values()))

    def validateTestRun(self, test_run):
        if not isinstance(test_run, bool):
            raise ValueError(
                'Test run param needs to be a bool. %s is not.'
                % test_run)

    def validateAnalysisRuns(self, runs):
        if not isinstance(runs, int):
            raise ValueError(
                'Analysis runs needs to be an int. %s is not.'
                % runs)

    def validateSeasonYear(self, year):
        if not isinstance(year, int):
            raise ValueError(
                'Season needs to be an int. %s is not.'
                % year)

    def validateDate(self, date):
        datetime.datetime.strptime(date, '%Y-%m-%d')

    def validateInList(self, param, param_list):
        if param not in param_list:
            raise ValueError('%s not a valid input' % param)

    def validateWeightsMutator(self, weights_mutator):
        method = getattr(WeightsMutator(), weights_mutator)
        if not method:
            raise ValueError(
                '%s is not a valid WeightsMutator function' % weights_mutator)
