from Simulation import Simulation
from Game import StatCategories

weights = {
    StatCategories.B_TOTAL : .5,
    StatCategories.P_TOTAL : .5,
}

season_start = 2007
season_end = 2008
stats_years = ['career', 'previous', 'season']
stats_type = 'basic'

for season in range(season_start, season_end + 1):
    for stats_year in stats_years:
        print 'Running ' + str(season) + ' ' + stats_year + '...'
        test = Simulation(weights, season, stats_year, stats_type)
        test.setUseReliever(False)
        test.setSample(0,9)
        test.run()
