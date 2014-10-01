from Constants import *

# WARNING: Values passed back are not validated, so be sure that all of your
# weight values add to 1 and the stat category you're using works.
class WeightsMutator:

    @staticmethod
    def example(inning, outs, bases, winning):
        return {StatCategories.TOTAL : 1}
