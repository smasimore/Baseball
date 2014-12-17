CREATE TABLE sim_input (
  rand_bucket SMALLINT(2),
  gameid VARCHAR(25),
  home VARCHAR(5),
  away VARCHAR(5),
  pitching_h MEDIUMTEXT,
  pitching_a MEDIUMTEXT,
  batting_h MEDIUMTEXT,
  batting_a MEDIUMTEXT,
  error_rate_h FLOAT(5),
  error_rate_a FLOAT(5),
  stats_type VARCHAR(5),
  stats_year VARCHAR(8),
  season SMALLINT(4),
  game_date DATE,
  timestamp TIMESTAMP
) PARTITION BY LIST COLUMNS(season, stats_type, stats_year) (
    PARTITION p1950basiccareer VALUES IN ((1950, 'basic', 'career'))
);
