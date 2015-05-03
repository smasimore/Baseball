from Simulation import Simulation
from Game import StatCategories
import datetime

sim = Simulation({StatCategories.B_HOME_AWAY : 1.0})
sim.setGameDate(datetime.datetime.now().strftime("%Y-%m-%d"))
sim.run()
