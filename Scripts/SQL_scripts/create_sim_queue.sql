CREATE table sim_queue (
season smallint(4),
stats_year varchar(8),
stats_type varchar(5),
weights_i smallint(5),
weights mediumtext,
weights_mutator varchar(25),
use_reliever tinyint(1),
ran_sim tinyint(1),
priority smallint(4),
timestamp timestamp
)
PARTITION BY LIST (ran_sim) (
PARTITION P0 VALUES IN 0,
PARTITION P1 VALUES IN 1,
PARTITION P2 VALUES IN 2
);
