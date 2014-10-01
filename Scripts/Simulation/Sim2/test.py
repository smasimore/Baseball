from Simulation import Simulation
from Game import StatCategories

weights = {
    StatCategories.TOTAL : .1,
    StatCategories.HOME_AWAY : .2,
    StatCategories.PITCHER_HANDEDNESS : .2,
    StatCategories.PITCHER_ERA_BAND : .1,
    StatCategories.PITCHER_VS_BATTER : .2,
    StatCategories.SITUATION : .1,
    StatCategories.STADIUM : .1
}

test = Simulation(weights, 2014, 'current', 'basic', 'example')
test.setTestRun(True)
test.run()
