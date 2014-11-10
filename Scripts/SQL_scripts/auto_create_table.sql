CREATE TABLE sim_output (home_win_pct FLOAT, game_details MEDIUMTEXT, gameid VARCHAR(25), game_date DATE, date_ran_sim DATE, home VARCHAR(5), away VARCHAR(5), season SMALLINT(4), stats_year VARCHAR(8), stats_type VARCHAR(5), weights_i SMALLINT(5), weights MEDIUMTEXT, weights_mutator VARCHAR(25), analysis_runs SMALLINT(7), sim_game_date VARCHAR(10), timestamp TIMESTAMP) PARTITION BY LIST COLUMNS(season, weights_i) (
        PARTITION p19501 VALUES IN ((1950, 1)))
        ;
