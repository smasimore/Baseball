from Simulation import Simulation
from Game import StatCategories

weights = {
    StatCategories.B_TOTAL : .1,
    StatCategories.B_HOME_AWAY : .2,
    StatCategories.B_PITCHER_HANDEDNESS : .2,
    StatCategories.B_SITUATION : .5
}

test = Simulation(weights, 1950, 'career', 'basic')
test.setSample(0,4)
test.setDebugLogging(True)
test.run()
