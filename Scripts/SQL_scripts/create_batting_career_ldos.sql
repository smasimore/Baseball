CREATE table batting_career_ldos (
player_id VARCHAR(10),
split VARCHAR(25),
plate_appearances INT(5),
singles INT(5),
doubles INT(5),
triples INT(5),
home_runs INT(5),
walks INT(5),
strikeouts INT(5),
fly_outs INT(5),
ground_outs INT(5),
season INT(4),
ds DATE
)
PARTITION BY LIST (season) (
PARTITION P2014 VALUES IN (2014),
PARTITION P2015 VALUES IN (2015),
PARTITION P2016 VALUES IN (2016)
);
