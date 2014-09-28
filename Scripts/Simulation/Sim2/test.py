from Simulation import Simulation

weights = {
    'total' : .2,
    'home_away' : .8,
}

test = Simulation(weights, 2014, 'current', 'basic')
test.setTestRun(True)
test.setGameDate('2014-03-31')
test.run()
