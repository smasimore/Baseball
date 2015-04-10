CREATE table espn_fielding (
player_id VARCHAR(10),
player_name VARCHAR(25),
espn_id INT(10),
pos VARCHAR(10),
games_played INT(4),
games_started INT(4),
innings FLOAT(5),
total_chances INT(4),
putouts INT(4),
assists INT(4),
errors INT(4),
double_plays INT(4),
fielding_percentage FLOAT(5),
range_factor FLOAT(5),
zone_rating FLOAT(5),
passed_balls INT(4),
stolen_bases INT(4),
caught_stealing INT(4),
caught_stealing_percentage FLOAT(5),
catcher_s_earned_run_average FLOAT(5),
defensive_wins_above_replacement FLOAT(5),
season INT(4),
ts DATETIME,
ds DATE
)
PARTITION BY LIST (season) (
PARTITION P2014 VALUES IN (2014),
PARTITION P2015 VALUES IN (2015),
PARTITION P2016 VALUES IN (2016)
);
