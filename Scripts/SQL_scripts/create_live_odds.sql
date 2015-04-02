CREATE table live_odds (
game_time VARCHAR(25),
game_date DATE,
home VARCHAR(25),
away VARCHAR(25),
home_odds int(5),
away_odds int(5),
season INT(4),
ts DATETIME,
ds DATE
)
PARTITION BY LIST (season) (
PARTITION P2015 VALUES IN (2015),
PARTITION P2016 VALUES IN (2016)
);
