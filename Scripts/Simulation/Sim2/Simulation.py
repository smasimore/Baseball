import MySQL, time, datetime

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
        default_type

        %_win
        results (s/d/t/h/f/gb/so/steals/...)

    '''



    # VALIDATION LISTS - used to make sure params are acceptable.
    DATA_TYPES = [
        'basic',
        'magic',
    ]

    STATS = [
        'total',
        'home_away',
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



    # DEFAULT PARAMS - these can be set using setter functions.
    ANALYSIS_RUNS = 2000
    GAME_DATE = None # Set a date to run a specific day of games.
                     # Timespan not currently available.
    DEFAULT_THRESHOLD = 9 # Number of at bats required to no longer default.
    DEFAULT_TYPE = 'season'
    TEST_RUN = False




    def __init__(self,
        weights,
        season = time.strftime("%Y"),     # What season of games to run on
        stats_year = 'current',
        stats_type = 'basic'):

        self.validateWeights(weights, stats_type)
        self.validateSeasonYear(season)
        self.validateInList(stats_year, self.STATS_YEAR)
        self.validateInList(stats_type, self.DATA_TYPES)

        self.weights = weights
        self.season = season
        self.statsYear = stats_year
        self.statsType = stats_type

        # Extra, defaulted params. To change call setters.
        self.analysisRuns = self.ANALYSIS_RUNS
        self.gameDate = self.GAME_DATE
        self.defaultThreshold = self.DEFAULT_THRESHOLD
        self.defaultType = self.DEFAULT_TYPE
        self.testRun = self.TEST_RUN # Use this in conjunction with setting a
                                     # file with test data. Sim will use test
                                     # data rather than MySQL.


    # def run():
        # if test run, call runTest() --- fetches test data, runs, returns
        # test results
        # fetchData() --- queries SQL, formats data
        # runGames() --- does parallelization, calls Game class
        # exportResults() --- formats results, exports to SQL. Logs
        # params, data, results, and one example game to sim_log


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

    def setDefaultType(self, default_type):
        self.validateInList(default_type, self.DEFAULT_TYPES)
        self.defaultType = default_type

    ########## VALIDATION FUNCTIONS ##########

    def validateWeights(self, weights, stats_type):
        # Check if param is dictionary.
        if type(weights) is not dict:
            raise ValueError('Weight param needs to be a dict. %s is not.'
                % weights)
        # Check if stats in STATS.
        for stat in weights:
            self.validateInList(stat, self.STATS)
        # If stats_type is basic, make sure weights add to 1.
        if stats_type == 'basic':
            if sum(weights.values()) != 1:
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