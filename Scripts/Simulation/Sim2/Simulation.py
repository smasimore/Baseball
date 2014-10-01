import MySQL, time, datetime, json
from Constants import *
from Game import Game
from WeightsMutator import WeightsMutator
from multiprocessing import Process, Manager

class Simulation:

    '''

    STARTING TABLE:
        ds (date added) **partition**
        timestamp
        gameid (date . hour . home)
        game_date
        home
        away
        stats_type **partition**
        stats_year **partition** (don't need 2013, 2014, etc all in it,
                    just need career for defaulting)
        season **partition**

        pitching_h (name, handedness, bucket, era, innings, batter v pitcher,
                    reliever b v p, reliever bucket, text/mediumtext)
        pitching_a
        batting_h (include stadium, only include relevant h/a, l/r?, stadium,
                    pitcher bucket, reliever bucket, text/mediumtext)
        batting_a
        error_rate_h (team error rate)
        error_rate_a


    FINAL TABLE:
        ds (day running) **partition**
        timestamp (time running)
        gameid
        game_date
        home
        away
        weights_key **partition** -- function needed to convert to and from,
                                    varchar
        weights (mediumtext)
        season
        stats_type (basic, magic) **partition**
        stats_year (previous, current, career)
        analysis_runs
        default_threshold

        %_win
        results (s/d/t/h/f/gb/so/steals/...)

    '''

    ########## PRIVATE CONSTANTS ##########
    __TABLE = 'sim_input'
    __TEST_TABLE = 'sim_input_test'
    __THREADS = 10



    ########## PARAM VALIDATION LISTS ##########
    STATS_TYPES = [
        'basic',
        'magic',
    ]

    STATS = [
        StatCategories.TOTAL,
        StatCategories.HOME_AWAY,
        StatCategories.PITCHER_HANDEDNESS,
        StatCategories.PITCHER_ERA_BAND,
        StatCategories.PITCHER_VS_BATTER,
        StatCategories.SITUATION,
        StatCategories.STADIUM,
    ]

    STATS_YEAR = [
        'career',
        'current',
        'previous',
    ]

    DEFAULT_TYPES = [  # If type chosen that doesn't meet,
        'last_season', # threshold will default to league.
        'total',
        'career',
        'league',
    ]



    ########## DEFAULT PARAMS - use setter functions to override. ##########
    ANALYSIS_RUNS = 2000
    GAME_DATE = None # Set a date to run a specific day of games.
                     # Timespan not currently available.
    DEFAULT_THRESHOLD = 9 # Number of at bats required to no longer default.
    TEST_RUN = False



    def __init__(self,
        weights,
        season = time.strftime("%Y"),
        stats_year = 'current',
        stats_type = 'basic',
        weights_mutator = ''):

        self.validateWeights(weights, stats_type)
        self.validateSeasonYear(season)
        self.validateInList(stats_type, self.STATS_TYPES)
        self.validateInList(stats_year, self.STATS_YEAR)
        self.validateWeightsMutator(weights_mutator)

        self.weights = weights
        self.season = season
        self.statsYear = stats_year
        self.statsType = stats_type

        self.weightsMutator = weights_mutator

        # Extra, defaulted params. To change call setters.
        self.analysisRuns = self.ANALYSIS_RUNS
        self.gameDate = self.GAME_DATE
        self.defaultThreshold = self.DEFAULT_THRESHOLD
        self.testRun = self.TEST_RUN # Use this in conjunction with setting a
                                     # file with test data. Sim will use test
                                     # data rather than MySQL.


    def run(self):
        self.fetchInputData()
        self.runGames()
        # exportResults() --- formats results, exports to SQL. Logs
        # params, data, results, and one example game to sim_log


    def __runGame(self, row_number, sim_results):
        game = Game(self.weights, self.inputData[row_number])

        if self.weightsMutator:
            game.setWeightsMutator(self.weightsMutator)


    # Multiprocessing method that calls runGame.
    def runGames(self):
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
            range(threads)
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

    def fetchInputData(self):
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
        );

        # If gameDate set, only pull one day of data.
        if self.gameDate:
            query = query + " AND game_date = '%s'" % self.gameDate

        results = MySQL.read(query)

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
            formatted_results.append(formatted_row)

        self.inputData = formatted_results



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

    def setDefaultThreshold(self, threshold):
        self.validateDefaultThreshold(threshold)
        self.defaultThreshold = threshold



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

    def validateDefaultThreshold(self, threshold):
        if not isinstance(threshold, int):
            raise ValueError(
                'Default threshold param needs to be an int. %s is not.'
                % test_run)

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
                '%s is not a valid WeightsMutator function' %weights_mutator)
