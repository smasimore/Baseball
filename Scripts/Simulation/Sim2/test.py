from Simulation import Simulation
from Game import StatCategories

weights = {
    StatCategories.TOTAL : .1,
    StatCategories.HOME_AWAY : .2,
    StatCategories.PITCHER_HANDEDNESS : .2,
    StatCategories.SITUATION : .5
}

test = Simulation(1.0, weights, 1950, 'career', 'basic')
#test.setTestRun(True)
#test.setWeightsMutator('example')
test.run()
