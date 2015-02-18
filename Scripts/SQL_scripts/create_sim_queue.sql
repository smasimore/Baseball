CREATE table sim_queue (
season smallint(4),
stats_year varchar(8),
stats_type varchar(5),
weights_i smallint(5),
weights mediumtext,
weights_mutator varchar(25),
use_reliever tinyint(1),
priority smallint(4),
pending bool,
timestamp timestamp
);
