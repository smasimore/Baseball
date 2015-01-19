from Simulation import Simulation
from Game import StatCategories

weights = {
    StatCategories.B_TOTAL : .5,
    StatCategories.P_TOTAL : .5,
}

season_start = 1955
season_end = 1955
stats_year = 'career'
stats_type = 'basic'

for season in range(season_start, season_end + 1):
    print 'Running ' + str(season) + '...'
    test = Simulation(weights, season, stats_year, stats_type)
    test.setUseReliever(False)
    test.setSample(0,9)
    test.run()
