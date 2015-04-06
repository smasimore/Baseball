CREATE table lineups (
time_est varchar(15),
away varchar(5),
home varchar(5),
away_pitcher_name varchar(25),
away_pitcher_id varchar(10),
home_pitcher_name varchar(25),
home_pitcher_id varchar(10),
away_handedness varchar(10),
home_handedness varchar(10),
away_lineup mediumtext,
home_lineup mediumtext,
season int(4),
ds DATE
)
PARTITION BY LIST (season) (
PARTITION P2015 VALUES IN (2015),
PARTITION P2016 VALUES IN (2016)
);
