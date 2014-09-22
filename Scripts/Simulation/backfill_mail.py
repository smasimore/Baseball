# PARAMS
# ANALYSIS_RUNS -- how many times to run each game
# PERCENT_SEASON_COMPLETED --  0 - 100


import csv
import time
import ast
import twostats_game
import sys, os, json
import operator
from functools import partial
from sys import argv
from datetime import datetime
from multiprocessing import Process, Manager

# time script
startTime = datetime.now()

# import arguments
script, ANALYSIS_RUNS, PERCENT_SEASON_COMPLETED = argv
PERCENT_SEASON_COMPLETED = float(PERCENT_SEASON_COMPLETED)
ANALYSIS_RUNS = int(ANALYSIS_RUNS)

# constants
NUM_THREADS = 10

def runGame(game_data, input_data, game_num):


    global PERCENT_SEASON_COMPLETED

    game_num = game_num + 1 # remove 0 game

    data = input_data[game_num]
    index_data = dict(data) # copying data so you have input csv
    if data['lineup_h_stats'] == '[]' or data['lineup_a_stats'] == '[]':
        index_data['home_sim_win'] = 'no stats'
        game_data[game_num] = index_data
        return
    home_batting = json.loads(data['lineup_h_stats'])
    away_batting = json.loads(data['lineup_a_stats'])
    stadium = json.loads(data['stadium_stats'])

    home_extra_info = {}
    away_extra_info = {}
    home_extra_info['fielding_mult_2013'] = data['fielding_mult_2013_home']
    home_extra_info['fielding_mult_2014'] = data['fielding_mult_2014_home']
    away_extra_info['fielding_mult_2013'] = data['fielding_mult_2013_away']
    away_extra_info['fielding_mult_2014'] = data['fielding_mult_2014_away']

    home_extra_info['percent_season_completed'] = PERCENT_SEASON_COMPLETED
    away_extra_info['percent_season_completed'] = PERCENT_SEASON_COMPLETED
    home_extra_info['home_away'] = 'Home'
    away_extra_info['home_away'] = 'Away'
    home_extra_info['pitcher_handedness'] = data['pitcher_a_handedness_i']
    away_extra_info['pitcher_handedness'] = data['pitcher_h_handedness_i']
    home_extra_info['pitcher_band_2013'] = 'PitcherTotal'
    away_extra_info['pitcher_band_2013'] = 'PitcherTotal'
    home_extra_info['pitcher_band_2014'] = 'PitcherTotal'
    away_extra_info['pitcher_band_2014'] = 'PitcherTotal'
    home_extra_info['stadium'] = stadium
    away_extra_info['stadium'] = stadium

    print data['home_i']
    home_results, home_details = twostats_game.playGame(home_batting, home_extra_info, ANALYSIS_RUNS)
    away_results, away_details = twostats_game.playGame(away_batting, away_extra_info, ANALYSIS_RUNS)

    if home_results == 'incomplete data' or away_results == 'incomplete data':
        index_data['home_sim_win'] = 'incomplete data'
        game_data[game_num] = index_data
        return
    home_games_won = 0
    away_games_won = 0
    total_home_runs = 0
    total_away_runs = 0
    # this needs to be # of analysis runs
    for i in range(ANALYSIS_RUNS):
        home_runs = home_results[i]
        away_runs = away_results[i]
        total_home_runs += home_runs
        total_away_runs += away_runs
        if home_runs > away_runs:
            home_games_won += 1
        elif away_runs > home_runs:
            away_games_won += 1

    index_data['home_avg_runs'] = float(total_home_runs) / float(ANALYSIS_RUNS)
    index_data['away_avg_runs'] = float(total_away_runs) / float(ANALYSIS_RUNS)
    index_data['home_avg_singles'] = sum(home_details['singles']) / float(len(home_details['singles']))
    index_data['away_avg_singles'] = sum(away_details['singles']) / float(len(away_details['singles']))
    index_data['home_avg_doubles'] = sum(home_details['doubles']) / float(len(home_details['doubles']))
    index_data['away_avg_doubles'] = sum(away_details['doubles']) / float(len(away_details['doubles']))
    index_data['home_avg_triples'] = sum(home_details['triples']) / float(len(home_details['triples']))
    index_data['away_avg_triples'] = sum(away_details['triples']) / float(len(away_details['triples']))
    index_data['home_avg_homeruns'] = sum(home_details['homeruns']) / float(len(home_details['homeruns']))
    index_data['away_avg_homeruns'] = sum(away_details['homeruns']) / float(len(away_details['homeruns']))
    index_data['home_avg_walks'] = sum(home_details['walks']) / float(len(home_details['walks']))
    index_data['away_avg_walks'] = sum(away_details['walks']) / float(len(away_details['walks']))
    index_data['home_avg_strikeouts'] = sum(home_details['strikeouts']) /float(len(home_details['strikeouts']))
    index_data['away_avg_strikeouts'] = sum(away_details['strikeouts']) /float(len(away_details['strikeouts']))
    index_data['home_avg_groundouts'] = sum(home_details['groundouts']) /float(len(home_details['groundouts']))
    index_data['away_avg_groundouts'] = sum(away_details['groundouts']) / float(len(away_details['groundouts']))
    index_data['home_avg_flyouts'] = sum(home_details['flyouts']) / float(len(home_details['flyouts']))
    index_data['away_avg_flyouts'] = sum(away_details['flyouts']) / float(len(away_details['flyouts']))
    index_data['home_avg_doubleplays'] = sum(home_details['doubleplays'])/ float(len(home_details['doubleplays']))
    index_data['away_avg_doubleplays'] = sum(away_details['doubleplays'])/ float(len(away_details['doubleplays']))
    index_data['home_avg_errors'] = sum(home_details['errors'])/float(len(home_details['errors']))
    index_data['away_avg_errors'] = sum(away_details['errors'])/float(len(away_details['errors']))

    # add percent win data to game_data
    if home_games_won != 0 or away_games_won != 0:
        home_sim_win = float(home_games_won) / (float)(home_games_won + away_games_won)
        index_data['home_sim_win'] = home_sim_win
    else:
        index_data['home_sim_win'] = 'Tried to divide by zero'
    game_data[game_num] = index_data

    return

def buildProcessesList(threads):
    l = []
    counter = 1
    while counter <= threads:
        value = 'p' + str(counter)
        l.append(value)
        counter += 1
    return l

# create shared memory dict
manager = Manager()
game_data = manager.dict()

# import base data
input_data = {}
with open ('input.csv', 'rb') as csvfile:
    game_reader = csv.DictReader(csvfile)
    index = 0
    for row in game_reader:
        gamenum = row['gamenumber']
        gamenum = int(gamenum)
        input_data[gamenum] = row
        index += 1

# parallel processing
num_games = len(input_data)
l = buildProcessesList(NUM_THREADS)
counter = 0
while counter < num_games:
    processes = {}
    for index, process in enumerate(l):
        # make sure not over num of games
        game_num = counter + index
        # TO DO: will probably have to delete 2 lines below and every other instances when make chrono changes since you could have this be true but not want to break
        if game_num == num_games:
            break
        processes[process] = Process(target=runGame,
args=(game_data,input_data,game_num))
    for index, process in enumerate(l):
        # make sure not over num of games
        game_num = counter + index
        if game_num == num_games:
            break
        processes[process].start()
    for index, process in enumerate(l):
        # make sure not over num of games
        game_num = counter + index
        if game_num == num_games:
            break
        processes[process].join()
    counter += NUM_THREADS
    print 'Game ' + str(game_num + 1)

# write to csv
with open('output.csv', 'wb') as f:
    w = csv.DictWriter(f, fieldnames = game_data[1].keys())
    w.writeheader()
    for key, row in game_data.items():
        w.writerow(row)

# print time script took
print(datetime.now()-startTime)
