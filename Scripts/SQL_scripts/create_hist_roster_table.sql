CREATE table retrosheet_historical_pitching_rosters (
team_id VARCHAR(3),
player_id VARCHAR(8),
last_name_tx VARCHAR(25),
first_name_tx VARCHAR(25),
bat_hand_cd VARCHAR(1),
pit_hand_cd VARCHAR(1),
season INT,
ds DATE
)
PARTITION BY LIST (season) (
PARTITION P1950 VALUES IN (1950),
PARTITION P1951 VALUES IN (1951),
PARTITION P1952 VALUES IN (1952),
PARTITION P1953 VALUES IN (1953),
PARTITION P1954 VALUES IN (1954),
PARTITION P1955 VALUES IN (1955),
PARTITION P1956 VALUES IN (1956),
PARTITION P1957 VALUES IN (1957),
PARTITION P1958 VALUES IN (1958),
PARTITION P1959 VALUES IN (1959),
PARTITION P1960 VALUES IN (1960),
PARTITION P1961 VALUES IN (1961),
PARTITION P1962 VALUES IN (1962),
PARTITION P1963 VALUES IN (1963),
PARTITION P1964 VALUES IN (1964),
PARTITION P1965 VALUES IN (1965),
PARTITION P1966 VALUES IN (1966),
PARTITION P1967 VALUES IN (1967),
PARTITION P1968 VALUES IN (1968),
PARTITION P1969 VALUES IN (1969),
PARTITION P1970 VALUES IN (1970),
PARTITION P1971 VALUES IN (1971),
PARTITION P1972 VALUES IN (1972),
PARTITION P1973 VALUES IN (1973),
PARTITION P1974 VALUES IN (1974),
PARTITION P1975 VALUES IN (1975),
PARTITION P1976 VALUES IN (1976),
PARTITION P1977 VALUES IN (1977),
PARTITION P1978 VALUES IN (1978),
PARTITION P1979 VALUES IN (1979),
PARTITION P1980 VALUES IN (1980),
PARTITION P1981 VALUES IN (1981),
PARTITION P1982 VALUES IN (1982),
PARTITION P1983 VALUES IN (1983),
PARTITION P1984 VALUES IN (1984),
PARTITION P1985 VALUES IN (1985),
PARTITION P1986 VALUES IN (1986),
PARTITION P1987 VALUES IN (1987),
PARTITION P1988 VALUES IN (1988),
PARTITION P1989 VALUES IN (1989),
PARTITION P1990 VALUES IN (1990),
PARTITION P1991 VALUES IN (1991),
PARTITION P1992 VALUES IN (1992),
PARTITION P1993 VALUES IN (1993),
PARTITION P1994 VALUES IN (1994),
PARTITION P1995 VALUES IN (1995),
PARTITION P1996 VALUES IN (1996),
PARTITION P1997 VALUES IN (1997),
PARTITION P1998 VALUES IN (1998),
PARTITION P1999 VALUES IN (1999),
PARTITION P2000 VALUES IN (2000),
PARTITION P2001 VALUES IN (2001),
PARTITION P2002 VALUES IN (2002),
PARTITION P2003 VALUES IN (2003),
PARTITION P2004 VALUES IN (2004),
PARTITION P2005 VALUES IN (2005),
PARTITION P2006 VALUES IN (2006),
PARTITION P2007 VALUES IN (2007),
PARTITION P2008 VALUES IN (2008),
PARTITION P2009 VALUES IN (2009),
PARTITION P2010 VALUES IN (2010),
PARTITION P2011 VALUES IN (2011),
PARTITION P2012 VALUES IN (2012),
PARTITION P2013 VALUES IN (2013),
PARTITION P2014 VALUES IN (2014),
PARTITION P2015 VALUES IN (2015),
PARTITION P2016 VALUES IN (2016)
);
