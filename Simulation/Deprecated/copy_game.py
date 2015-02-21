import urllib2
import sys, json
import random
import time
import csv
import os
from termcolor import colored
from sys import argv
from multiprocessing import Process, Manager
from datetime import datetime

# TO DO: add sacrifice fly (if fly and man on third and < 2 outs), steals

# constants
MAX_AT_BATS = 9
PERCENT_SEASON_COMPLETED = 0
ANALYSIS_RUNS = 100

# global variables
game_results = {}
game_details = {'singles' : [], 'doubles' : [], 'triples' : [], 'homeruns' : [], 'walks' : [], 'strikeouts' : [], 'groundouts' : [], 'flyouts' : [], 'doubleplays' : []}
home_away = 'Home'
handedness = 'Unknown'
pitcher_band_2013 = 'Unknown'
pitcher_band_2014 = 'Unknown'
situation = 'Unknown'
runs = 0
bases = {'first' : 0, 'second' : 0, 'third' : 0}
outs = 0
singles = 0
doubles = 0
triples = 0
homeruns = 0
walks = 0
strikeouts = 0
groundouts = 0
flyouts = 0
doubleplays = 0
errors = 0

batting_2013 = {}
batting_2014 = {}
stadium_2013 = {}
stadium_2014 = {}
extra_info = {}

# EXPERIMENT TO ONES IN RESEARCH
league_stats = {
    'first_to_third_on_single' : .313, 'first_to_home_on_double' : .433,
'second_to_home_on_single' : .653, 'second_to_home_on_double' : .984,
'double_play_avg_when_possible' : .5,'double_play_avg_when_possible' : .5,
'f_s_first_second_double_play' : .5, 'f_s_first_third_double_play' : .3,
'f_s_second_third_double_play' : .2, 'f_s_t_first_second_double_play' : .4,
'f_s_t_first_third_double_play' : .2, 'f_s_t_second_third_double_play' : .1,
'f_s_t_home_first_double_play' : .1, 'f_s_t_home_second_double_play' : .1,
'f_s_t_home_third_double_play' : .1
}

# EXPERIMENT -- batting stat weights
batting_weights = {'Total' : .5, 'Handedness' : 0, 'PitcherBand' : .5,
'Situation' : 0, 'HomeAway' : 0, 'Stadium' : 0}

def printBases():
    global bases
    if bases['second'] == 1:
        print colored('      []    ', 'yellow')
    else:
        print '      []'
    print '    /    \ '
    print '  /        \ '
    if bases['third'] == 1 and bases['first'] == 1:
        print colored('[]          []', 'yellow')
    elif bases['third'] == 1 and bases['first'] == 0:
        print colored('[]', 'yellow') + '          []'
    elif bases['third'] == 0 and bases['first'] == 1:
       print '[]' + colored('          []', 'yellow')
    else:
        print '[]          []'
    print '  \        / '
    print '    \    / '
    print '      []'

# TO DO: convert to for loop
def calculateWeightedBatterStats(batter_stats):
    if bases['first'] == 1 and bases['second'] == 0 and bases['third'] == 0:
        situation = 'RunnersOn'
    elif bases['first'] == 1 and bases['second'] == 1 and bases['third'] == 1:
        situation = 'BasesLoaded'
    elif outs == 2 and (bases['second'] == 1 or bases['third'] == 1):
        situation = 'ScoringPos2Out'
    elif bases['second'] == 1 or bases['third'] == 1:
        situation = 'ScoringPos'
    else:
        situation = 'NoneOn'

    # EXPERIMENT
    if PERCENT_SEASON_COMPLETED < 1:
        pitcher_band = pitcher_band_2013
    else:
        pitcher_band = pitcher_band_2014

    weighted_batter_stats = {}
    weighted_batter_stats['pct_single'] = (batting_weights['Total']*batter_stats['Total']['pct_single'] +
                          batting_weights['Handedness']*batter_stats[handedness]['pct_single'] +
                          batting_weights['PitcherBand']*batter_stats[pitcher_band]['pct_single'] +
                          batting_weights['Situation']*batter_stats[situation]['pct_single'] +
                          batting_weights['HomeAway']*batter_stats[home_away]['pct_single'] +
                          batting_weights['Stadium']*float(batter_stats['stadium']['pct_single']))
    weighted_batter_stats['pct_double'] = (batting_weights['Total']*batter_stats['Total']['pct_double'] +
                          batting_weights['Handedness']*batter_stats[handedness]['pct_double'] +
                          batting_weights['PitcherBand']*batter_stats[pitcher_band]['pct_double'] +
                          batting_weights['Situation']*batter_stats[situation]['pct_double'] +
                          batting_weights['HomeAway']*batter_stats[home_away]['pct_double'] +
                          batting_weights['Stadium']*float(batter_stats['stadium']['pct_double']))
    weighted_batter_stats['pct_triple'] = (batting_weights['Total']*batter_stats['Total']['pct_triple'] +
                          batting_weights['Handedness']*batter_stats[handedness]['pct_triple'] +
                          batting_weights['PitcherBand']*batter_stats[pitcher_band]['pct_triple'] +
                          batting_weights['Situation']*batter_stats[situation]['pct_triple'] +
                          batting_weights['HomeAway']*batter_stats[home_away]['pct_triple'] +
                          batting_weights['Stadium']*float(batter_stats['stadium']['pct_triple']))
    weighted_batter_stats['pct_home_run'] = (batting_weights['Total']*batter_stats['Total']['pct_home_run'] +
                          batting_weights['Handedness']*batter_stats[handedness]['pct_home_run'] +
                          batting_weights['PitcherBand']*batter_stats[pitcher_band]['pct_home_run'] +
                          batting_weights['Situation']*batter_stats[situation]['pct_home_run'] +
                          batting_weights['HomeAway']*batter_stats[home_away]['pct_home_run'] +
                          batting_weights['Stadium']*float(batter_stats['stadium']['pct_home_run']))
    weighted_batter_stats['pct_walk'] = (batting_weights['Total']*batter_stats['Total']['pct_walk'] +
                          batting_weights['Handedness']*batter_stats[handedness]['pct_walk'] +
                          batting_weights['PitcherBand']*batter_stats[pitcher_band]['pct_walk'] +
                          batting_weights['Situation']*batter_stats[situation]['pct_walk'] +
                          batting_weights['HomeAway']*batter_stats[home_away]['pct_walk'] +
                          batting_weights['Stadium']*float(batter_stats['stadium']['pct_walk']))
    weighted_batter_stats['pct_strikeout'] = (batting_weights['Total']*batter_stats['Total']['pct_strikeout'] +
                          batting_weights['Handedness']*batter_stats[handedness]['pct_strikeout'] +
                          batting_weights['PitcherBand']*batter_stats[pitcher_band]['pct_strikeout'] +
                          batting_weights['Situation']*batter_stats[situation]['pct_strikeout'] +
                          batting_weights['HomeAway']*batter_stats[home_away]['pct_strikeout'] +
                          batting_weights['Stadium']*float(batter_stats['stadium']['pct_strikeout']))
    weighted_batter_stats['pct_ground_out'] = (batting_weights['Total']*batter_stats['Total']['pct_ground_out'] +
                          batting_weights['Handedness']*batter_stats[handedness]['pct_ground_out'] +
                          batting_weights['PitcherBand']*batter_stats[pitcher_band]['pct_ground_out'] +
                          batting_weights['Situation']*batter_stats[situation]['pct_ground_out'] +
                          batting_weights['HomeAway']*batter_stats[home_away]['pct_ground_out'] +
                          batting_weights['Stadium']*float(batter_stats['stadium']['pct_ground_out']))
    weighted_batter_stats['pct_fly_out'] = (batting_weights['Total']*batter_stats['Total']['pct_fly_out'] +
                          batting_weights['Handedness']*batter_stats[handedness]['pct_fly_out'] +
                          batting_weights['PitcherBand']*batter_stats[pitcher_band]['pct_fly_out'] +
                          batting_weights['Situation']*batter_stats[situation]['pct_fly_out'] +
                          batting_weights['HomeAway']*batter_stats[home_away]['pct_fly_out'] +
                          batting_weights['Stadium']*float(batter_stats['stadium']['pct_fly_out']))

    return weighted_batter_stats

def calculateAtBatValue(batter):
    batter_l = 'L' + str(batter)
    print batter_l
    batter_stats_2013 = batting_2013[batter_l]
    batter_stats_2014 = batting_2014[batter_l]

    batter_stats_2013['stadium'] = stadium_2013[home_away]
    batter_stats_2014['stadium'] = stadium_2014[home_away]

    weighted_batter_stats_2013 = calculateWeightedBatterStats(batter_stats_2013)
    weighted_batter_stats_2014 = calculateWeightedBatterStats(batter_stats_2014)

    # consider weighting it by more granular features like batting avg
    pct_single_2013 = weighted_batter_stats_2013['pct_single']
    pct_double_2013 = weighted_batter_stats_2013['pct_double']
    pct_triple_2013 = weighted_batter_stats_2013['pct_triple']
    pct_home_run_2013 = weighted_batter_stats_2013['pct_home_run']
    pct_walk_2013 = weighted_batter_stats_2013['pct_walk']
    pct_strikeout_2013 = weighted_batter_stats_2013['pct_strikeout']
    pct_ground_out_2013 = weighted_batter_stats_2013['pct_ground_out']
    pct_fly_out_2013 = weighted_batter_stats_2013['pct_fly_out']

    pct_single_2014 = weighted_batter_stats_2014['pct_single']
    pct_double_2014 = weighted_batter_stats_2014['pct_double']
    pct_triple_2014 = weighted_batter_stats_2014['pct_triple']
    pct_home_run_2014 = weighted_batter_stats_2014['pct_home_run']
    pct_walk_2014 = weighted_batter_stats_2014['pct_walk']
    pct_strikeout_2014 = weighted_batter_stats_2014['pct_strikeout']
    pct_ground_out_2014 = weighted_batter_stats_2014['pct_ground_out']
    pct_fly_out_2014 = weighted_batter_stats_2014['pct_fly_out']

    single_max = PERCENT_SEASON_COMPLETED*pct_single_2014 + (1 - PERCENT_SEASON_COMPLETED)*pct_single_2013
    double_max = PERCENT_SEASON_COMPLETED*pct_double_2014 + (1 - PERCENT_SEASON_COMPLETED)*pct_double_2013 + single_max
    triple_max = PERCENT_SEASON_COMPLETED*pct_triple_2014 + (1 - PERCENT_SEASON_COMPLETED)*pct_triple_2013 + double_max
    home_run_max = PERCENT_SEASON_COMPLETED*pct_home_run_2014 + (1 - PERCENT_SEASON_COMPLETED)*pct_home_run_2013 + triple_max
    walk_max = PERCENT_SEASON_COMPLETED*pct_walk_2014 + (1 - PERCENT_SEASON_COMPLETED)*pct_walk_2013 + home_run_max
    strikeout_max = PERCENT_SEASON_COMPLETED*pct_strikeout_2014 + (1 - PERCENT_SEASON_COMPLETED)*pct_strikeout_2013 + walk_max
    ground_out_max = PERCENT_SEASON_COMPLETED*pct_ground_out_2014 + (1 - PERCENT_SEASON_COMPLETED)*pct_ground_out_2013 + strikeout_max
    fly_out_max = PERCENT_SEASON_COMPLETED*pct_fly_out_2014 + (1 - PERCENT_SEASON_COMPLETED)*pct_fly_out_2013 + ground_out_max

    rand = random.random()

    atbat_value = 'null'
    if rand <= single_max:
        atbat_value = 'single'
        global singles
        singles += 1
    elif rand > single_max and rand <= double_max:
        atbat_value = 'double'
        global doubles
        doubles += 1
    elif rand > double_max and rand <= triple_max:
        atbat_value = 'triple'
        global triples
        triples += 1
    elif rand > triple_max and rand <= home_run_max:
        atbat_value = 'home run'
        global homeruns
        homeruns += 1
    elif rand > home_run_max and rand <= walk_max:
        atbat_value = 'walk'
        global walks
        walks += 1
    elif rand > walk_max and rand <= strikeout_max:
        atbat_value = 'strikeout'
        global strikeouts
        strikeouts += 1
    elif rand > strikeout_max and rand <= ground_out_max:
        atbat_value = 'ground out'
        global groundouts
        groundouts += 1
    elif rand > ground_out_max and rand <= fly_out_max:
        atbat_value = 'fly out'
        global flyouts
        flyouts += 1
    return atbat_value


def calculateHitImpact(atbat_value):
    global bases
    global runs

    # no player on base
    if bases['first'] == 0 and bases['second'] == 0 and bases['third'] == 0:
        bases = {'first' : 0, 'second' : 0, 'third' : 0}
        if atbat_value == 'single':
            bases['first'] = 1
        elif atbat_value == 'double':
            bases['second'] = 1
        elif atbat_value == 'triple':
            bases['third'] = 1
        elif atbat_value == 'home run':
            print colored('Run scored!', 'yellow')
            runs += 1

    # 1 player on base
    elif bases['first'] == 1 and bases['second'] == 0 and bases['third'] == 0:
        bases = {'first' : 0, 'second' : 0, 'third' : 0}
        if atbat_value == 'single':
            bases['first'] = 1
            if random.random() <= league_stats['first_to_third_on_single']:
                bases['third'] = 1
            else:
                bases['second'] = 1
        elif atbat_value == 'double':
            bases['second'] = 1
            if random.random() <= league_stats['first_to_home_on_double']:
                print colored('Run scored!', 'yellow')
                runs += 1
            else:
                bases['third'] = 1
        elif atbat_value == 'triple':
            bases['third'] = 1
            print colored('Run scored!', 'yellow')
            runs += 1
        elif atbat_value == 'home run':
            print colored('2 runs scored!', 'yellow')
            runs += 2

    elif bases['first'] == 0 and bases['second'] == 1 and bases['third'] == 0:
        bases = {'first' : 0, 'second' : 0, 'third' : 0}
        if atbat_value == 'single':
            bases['first'] = 1
            if random.random() <= league_stats['second_to_home_on_single']:
                print colored('Run scored!', 'yellow')
                runs += 1
            else:
                bases['third'] = 1
        elif atbat_value == 'double':
            bases['second'] = 1
            if random.random() <= league_stats['second_to_home_on_double']:
                print colored('Run scored!', 'yellow')
                runs += 1
            else:
                bases['third'] = 1
        elif atbat_value == 'triple':
            bases['third'] = 1
            print colored('Run scored!', 'yellow')
            runs += 1
        elif atbat_value == 'home run':
            print colored('2 runs scored!', 'yellow')
            runs += 2

    elif bases['first'] == 0 and bases['second'] == 0 and bases['third'] == 1:
        bases = {'first' : 0, 'second' : 0, 'third' : 0}
        if atbat_value == 'single':
            bases['first'] = 1
            print colored('Run scored!', 'yellow')
            runs += 1
        elif atbat_value == 'double':
            bases['second'] = 1
            print colored('Run scored!', 'yellow')
            runs += 1
        elif atbat_value == 'triple':
            bases['third'] = 1
            print colored('Run scored!', 'yellow')
            runs += 1
        elif atbat_value == 'home run':
            print colored('2 runs scored!', 'yellow')
            runs += 2

    # 2 players on base
    elif bases['first'] == 1 and bases['second'] == 1 and bases['third'] == 0:
        bases = {'first' : 0, 'second' : 0, 'third' : 0}
        if atbat_value == 'single':
            bases['first'] = 1
            if random.random() <= league_stats['second_to_home_on_single']:
                print colored('Run scored!', 'yellow')
                runs += 1
                if random.random() <= league_stats['first_to_third_on_single']:
                    bases['third'] = 1
                else:
                    bases['second'] = 1
            else:
                bases['second'] = 1
                bases['third'] = 1
        elif atbat_value == 'double':
            bases['second'] = 1
            print colored('Run scored!', 'yellow')
            runs += 1
            if random.random() <= league_stats['first_to_home_on_double']:
                print colored('Run scored!', 'yellow')
                runs += 1
            else:
                bases['third'] = 1
        elif atbat_value == 'triple':
            bases['third'] = 1
            print colored('2 runs scored!', 'yellow')
            runs += 2
        elif atbat_value == 'home run':
            print colored('3 runs scored!', 'yellow')
            runs += 3

    elif bases['first'] == 1 and bases['second'] == 0 and bases['third'] == 1:
        bases = {'first' : 0, 'second' : 0, 'third' : 0}
        if atbat_value == 'single':
            bases['first'] = 1
            print colored('Run scored!', 'yellow')
            runs += 1
            if random.random() <= league_stats['first_to_third_on_single']:
                bases['third'] = 1
            else:
                bases['second'] = 1
        elif atbat_value == 'double':
            bases['second'] = 1
            print colored('Run scored!', 'yellow')
            runs += 1
            if random.random() <= league_stats['first_to_home_on_double']:
                print colored('Run scored!', 'yellow')
                runs += 1
            else:
                bases['third'] = 1
        elif atbat_value == 'triple':
            bases['third'] = 1
            print colored('2 runs scored!', 'yellow')
            runs += 2
        elif atbat_value == 'home run':
            print colored('3 runs scored!', 'yellow')
            runs += 3

    elif bases['first'] == 0 and bases['second'] == 1 and bases['third'] == 1:
        bases = {'first' : 0, 'second' : 0, 'third' : 0}
        if atbat_value == 'single':
            bases['first'] = 1
            print colored('Run scored!', 'yellow')
            runs += 1
            if random.random() <= league_stats['second_to_home_on_single']:
                print colored('Run scored!', 'yellow')
                runs += 1
            else:
                bases['third'] = 1
        elif atbat_value == 'double':
            bases['second'] = 1
            print colored('Run scored!', 'yellow')
            runs += 1
            if random.random() <= league_stats['second_to_home_on_double']:
                print colored('Run scored!', 'yellow')
                runs += 1
            else:
                bases['third'] = 1
        elif atbat_value == 'triple':
            bases['third'] = 1
            print colored('2 runs scored!', 'yellow')
            runs += 2
        elif atbat_value == 'home run':
            print colored('3 runs scored!', 'yellow')
            runs += 3

    # bases loaded
    elif bases['first'] == 1 and bases['second'] == 1 and bases['third'] == 1:
        bases = {'first' : 0, 'second' : 0, 'third' : 0}
        if atbat_value == 'single':
            bases['first'] = 1
            print colored('Run scored!', 'yellow')
            runs += 1
            if random.random() <= league_stats['second_to_home_on_single']:
                print colored('Run scored!', 'yellow')
                runs += 1
                # For these guys might want to just assume other player goes 2 bases as well
                if random.random() <= league_stats['first_to_third_on_single']:
                    bases['third'] = 1
                else:
                    bases['second'] = 1
            else:
                bases['second'] = 1
                bases['third'] = 1
        elif atbat_value == 'double':
            bases['second'] = 1
            print colored('2 runs scored!', 'yellow')
            runs += 2
            if random.random() <= league_stats['first_to_home_on_double']:
                print colored('Run scored!', 'yellow')
                runs += 1
            else:
                bases['third'] = 1
        elif atbat_value == 'triple':
            bases['third'] = 1
            print colored('3 runs scored!', 'yellow')
            runs += 3
        elif atbat_value == 'home run':
            print colored('4 runs scored!', 'yellow')
            runs += 4


def calculateOutImpact(out_value):
    global outs
    global bases
    global runs

    # no double play opportunity
    if out_value == 'strikeout' or out_value == 'fly out' or bases['first'] == 0 or outs == 2:
        outs += 1
        if out_value == 'strikeout':
            print 'Strike 3!'
        elif out_value == 'fly out':
            print 'Fly out'
        elif out_value == 'ground out':
            print 'Ground out'
        return

    if random.random() <= league_stats['double_play_avg_when_possible']:
        outs += 2
        # didn't include third base since that will take care of man on first and man on first and third cases
        rand = random.random()
        if bases['first'] == 1 and bases['second'] == 0:
            bases['first'] = 0

        elif bases['first'] == 1 and bases['second'] == 1 and bases['third'] == 0:
            rand = random.random()
            if rand <= league_stats['f_s_first_second_double_play']:
                bases['third'] = 1
                bases['first'] = 0
            elif (rand > league_stats['f_s_first_second_double_play'] and
                 rand <= league_stats['f_s_first_second_double_play'] + league_stats['f_s_first_third_double_play']):
                bases['first'] = 0
            elif (rand > league_stats['f_s_first_second_double_play'] + league_stats['f_s_first_third_double_play'] and
                  rand <= league_stats['f_s_first_second_double_play'] + league_stats['f_s_first_third_double_play']
                  + league_stats['f_s_second_third_double_play']):
                bases['second'] = 0

        elif bases['first'] == 1 and bases['second'] == 1 and bases['third'] == 1:
            if rand <= league_stats['f_s_t_first_second_double_play']:
                bases['third'] = 1
                bases['second'] = 0
                bases['first'] = 0
                runs += 1
                print colored('Run scored!', 'yellow')
            elif (rand > league_stats['f_s_t_first_second_double_play'] and
                 rand <= league_stats['f_s_t_first_second_double_play'] + league_stats['f_s_t_first_third_double_play']):
                bases['first'] = 0
                bases['third'] = 0
                runs += 1
                print colored('Run scored!', 'yellow')
            elif (rand > league_stats['f_s_t_first_second_double_play'] + league_stats['f_s_t_first_third_double_play'] and
                  rand <= league_stats['f_s_t_first_second_double_play'] + league_stats['f_s_t_first_third_double_play']
                  + league_stats['f_s_t_second_third_double_play']):
                bases['second'] = 0
                bases['third'] = 0
                runs += 1
                print colored('Run scored!', 'yellow')
            elif (rand > league_stats['f_s_t_first_second_double_play'] + league_stats['f_s_t_first_third_double_play']
                  + league_stats['f_s_t_first_third_double_play'] and
                  rand <= league_stats['f_s_t_first_second_double_play'] + league_stats['f_s_t_first_third_double_play']
                  + league_stats['f_s_t_second_third_double_play'] + league_stats['f_s_t_home_first_double_play']):
                bases['first'] = 0
            elif (rand > league_stats['f_s_t_first_second_double_play'] + league_stats['f_s_t_first_third_double_play']
                  + league_stats['f_s_t_first_third_double_play'] + league_stats['f_s_t_home_first_double_play'] and
                  rand <= league_stats['f_s_t_first_second_double_play'] + league_stats['f_s_t_first_third_double_play']
                  + league_stats['f_s_t_second_third_double_play'] + league_stats['f_s_t_home_first_double_play']
                  + league_stats['f_s_t_home_second_double_play']):
                bases['second'] = 0
            elif (rand > league_stats['f_s_t_first_second_double_play'] + league_stats['f_s_t_first_third_double_play']
                  + league_stats['f_s_t_first_third_double_play'] + league_stats['f_s_t_home_first_double_play']
                  + league_stats['f_s_t_home_second_double_play'] and
                  rand <= league_stats['f_s_t_first_second_double_play'] + league_stats['f_s_t_first_third_double_play']
                  + league_stats['f_s_t_second_third_double_play'] + league_stats['f_s_t_home_first_double_play']
                  + league_stats['f_s_t_home_second_double_play'] + league_stats['f_s_t_home_third_double_play']):
                 bases['third'] = 0
        global doubleplays
        doubleplays += 1
        print colored('Double Play!', 'magenta')
    else:
        print 'Ground out'
        outs += 1

    return

def calculateWalkImpact():
    global bases
    global runs

    print colored('Walk', 'cyan')

    if bases['first'] == 0:
        bases['first'] = 1
    elif bases['first'] == 1 and bases['second'] == 0:
        bases['first'] = 1
        bases['second'] = 1
    elif bases['first'] == 1 and bases['second'] == 1 and bases['third'] == 0:
        bases['first'] = 1
        bases['second'] = 1
        bases['third'] = 1
    elif bases['first'] == 1 and bases['second'] == 1 and bases['third'] == 1:
        bases['first'] = 1
        bases['second'] = 1
        bases['third'] = 1
        runs += 1
        print colored('Run scored!', 'yellow')

    return

def atBat(batter):
    atbat_value = calculateAtBatValue(batter)

    # DELETE ME
    print 'atbat_value'
    print atbat_value

    if atbat_value == 'walk':
        calculateWalkImpact()
        return

    if atbat_value == 'ground out' or atbat_value == 'fly out':
        error_rate = 1 - (PERCENT_SEASON_COMPLETED*float(extra_info['fielding_mult_2014']) + (1 - PERCENT_SEASON_COMPLETED)*float(extra_info['fielding_mult_2013']))

        # EXPERIMENT - remove errors
        error_rate = 0

        rand = random.random()
        if rand <= float(error_rate):
            global errors
            errors += 1
            atbat_value = 'single'
            print 'Error'

    if atbat_value == 'single' or atbat_value == 'double' or atbat_value == 'triple' or atbat_value == 'home run':
        print colored(atbat_value + '!', 'cyan')
        calculateHitImpact(atbat_value)

    else:
        calculateOutImpact(atbat_value)

    return


def playGame(batting_input, extra_info_input, runs_input = 100):
    global ANALYSIS_RUNS
    global PERCENT_SEASON_COMPLETED
    global runs
    global bases
    global outs
    global singles
    global doubles
    global triples
    global homeruns
    global walks
    global strikeouts
    global groundouts
    global flyouts
    global doubleplays
    global batting_2013
    global batting_2014
    global home_away
    global handedness
    global pitcher_band_2013
    global pitcher_band_2014
    global stadium_2013
    global stadium_2014
    global extra_info
    global batting_weights

    batting_2013 = batting_input['2013']
    batting_2014 = batting_input['2014']
    stadium_2013 = extra_info_input['stadium']['2013']
    stadium_2014 = extra_info_input['stadium']['2014']
    extra_info = extra_info_input
    ANALYSIS_RUNS = runs_input

    # EXPERIMENT -- FIRST/SECOND HALF SPLIT
    if extra_info['percent_season_completed'] < 100:
        PERCENT_SEASON_COMPLETED = 0
    else:
        PERCENT_SEASON_COMPLETED = 1

    home_away = extra_info_input['home_away']
    handedness = extra_info_input['pitcher_handedness']
    pitcher_band_2013 = extra_info_input['pitcher_band_2013']
    pitcher_band_2014 = extra_info_input['pitcher_band_2014']
    game_results = {}
    game_details = {'singles' : [], 'doubles' : [], 'triples' : [], 'homeruns'
: [], 'walks' : [], 'strikeouts' : [], 'groundouts' : [], 'flyouts' : [],
'doubleplays' : [], 'errors' : []}

    # EXPERIMENT - stadium replaces homeaway for away team
    #if home_away == 'Away':
    #    batting_weights = {'Total' : .2, 'Handedness' : .2, 'PitcherBand' : .2, 'Situation' : .2, 'HomeAway' : 0, 'Stadium' : .2}

    # EXPERIMENT - handedness = VsRight for magic, = 'Total' for normal weighted
    # format data
    if handedness == 'Unknown':
        handedness = 'VsRight'
    elif handedness == 'R':
        handedness = 'VsRight'
    elif handedness == 'L':
        handedness = 'VsLeft'

    # silence prints
    sys.stdout = open(os.devnull, "w")

    # make sure data exists before moving forward
    if not batting_2013.viewkeys() >= {'L1', 'L2', 'L3', 'L4', 'L5', 'L6', 'L7', 'L8', 'L9'} or not batting_2014.viewkeys() >= {'L1', 'L2', 'L3', 'L4', 'L5', 'L6', 'L7', 'L8', 'L9'}:
        ANALYSIS_RUNS = 0
        return 'incomplete data', 'incomplete data'

    analysis_run = 0
    while analysis_run < ANALYSIS_RUNS:
        # play game
        runs = 0
        inning = 1
        batter = 1
        singles = 0
        doubles = 0
        triples = 0
        homeruns = 0
        walks = 0
        strikeouts = 0
        groundouts = 0
        flyouts = 0
        doubleplays = 0
        while inning <= 9:
            print '\r\n'
            print colored('inning: ' + str(inning), 'green')

            outs = 0;
            bases = {'first' : 0, 'second' : 0, 'third' : 0}
            while outs < 3:
                print colored('outs: ' + str(outs), 'red')
                print colored('runs: ' + str(runs), 'red')
                printBases()
                print 'batter: ' + str(batter)

                atBat(batter)

                # time.sleep(2)
                # next batter
                if batter < MAX_AT_BATS:
                    batter += 1
                else:
                    batter = 1

            # next inning
            inning += 1

        # save game runs to list
        game_results[analysis_run] = runs
        game_details['singles'].append(singles)
        game_details['doubles'].append(doubles)
        game_details['triples'].append(triples)
        game_details['homeruns'].append(homeruns)
        game_details['walks'].append(walks)
        game_details['strikeouts'].append(strikeouts)
        game_details['groundouts'].append(groundouts)
        game_details['flyouts'].append(flyouts)
        game_details['doubleplays'].append(doubleplays)
        game_details['errors'].append(errors)

        # next analysis run
        analysis_run += 1

    return game_results, game_details

def forcePrint(variable):
    sys.stdout = sys.__stdout__
    print '=============================='
    print variable
    print '=============================='
    sys.stdout = open(os.devnull, "w")

# silence prints
sys.stdout = sys.__stdout__
