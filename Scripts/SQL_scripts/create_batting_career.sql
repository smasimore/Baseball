CREATE table batting_career (
player_id VARCHAR(10),
stats MEDIUMTEXT,
season INT(4),
ds DATE
)
PARTITION BY LIST (season) (
PARTITION P2014 VALUES IN (2014),
PARTITION P2015 VALUES IN (2015),
PARTITION P2016 VALUES IN (2016)
);
