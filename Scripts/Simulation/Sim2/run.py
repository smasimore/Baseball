from Simulation import Simulation
from Game import StatCategories

weights = {
    StatCategories.B_TOTAL : 1.0,
}

season_start = 1950
season_end = 1951
stats_year = 'season'
stats_type = 'basic'

for season in range(season_start, season_end + 1):
    print 'Running ' + str(season) + '...'
    test = Simulation(weights, season, stats_year, stats_type)
    test.setSample(0,9)
    test.run()