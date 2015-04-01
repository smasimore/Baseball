CREATE table live_scores (
home VARCHAR(3),
away VARCHAR(3),
home_score INT(2),
away_score INT(2),
status VARCHAR(25),
game_date DATE,
game_time VARCHAR(25),
season INT(4),
ts DATETIME,
ds DATE
)
PARTITION BY LIST (season) (
PARTITION P2015 VALUES IN (2015),
PARTITION P2016 VALUES IN (2016)
);
