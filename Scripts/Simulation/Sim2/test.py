from Simulation import Simulation

weights = {
    'total' : .2,
    'home_away' : .8,
}

test = Simulation(weights, 2014, 'current', 'basic')
test.setTestRun(True)
test.run()
