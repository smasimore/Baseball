CREATE table odds (
game_time VARCHAR(25),
game_date DATE,
odds_date DATE,
odds_time VARCHAR(25),
home VARCHAR(25),
away VARCHAR(25),
casino VARCHAR(25),
home_odds int(5),
home_pct_win FLOAT(5),
away_odds int(5),
away_pct_win FLOAT(5),
season INT(4),
ts DATETIME,
ds DATE
)
PARTITION BY LIST (season) (
PARTITION P2014 VALUES IN (2014),
PARTITION P2015 VALUES IN (2015),
PARTITION P2016 VALUES IN (2016)
);
