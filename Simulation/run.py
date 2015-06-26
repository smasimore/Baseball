from Simulation import Simulation
from Game import StatCategories
from datetime import date

weights = {
    StatCategories.B_HOME_AWAY : 1.0,
}

season_start = 2015
season_end = 2015
stats_years = ['career']
stats_type = 'basic'

for season in range(season_start, season_end + 1):
    for stats_year in stats_years:
        print 'Running ' + str(season) + ' ' + stats_year + '...'
        test = Simulation(weights, season, stats_year, stats_type)
        test.setGameDate(str(date.today()))
        test.setUseReliever(False)
        test.run()
