# MySQL util functions

import MySQLdb, imp
from warnings import filterwarnings, resetwarnings

constants = imp.load_source('constants', '/Users/constants.py')

# Error types. Used to only raise certain types of errors.
_DUPLICATE_PARTITION = 1517

def __connectToDatabase():
    return MySQLdb.connect(
        constants.HOST,
        constants.DB_USER,
        constants.DB_PASSWORD,
        constants.BB_DATABASE
    )


def __printExceptionQuery(query):
    print
    print '************** EXCEPTION ON QUERY ***************'
    print query
    print '*************************************************'
    print

# create(
#   'test_python_integration3',
#   {'name' : 'varchar(20)', 'id' : 'int', 'dou' : 'float'}
# )
def create(table_name, column_data):
    formatted_column_data = ', '.join(
        [' '.join([key, val]) for key, val in column_data.items()]
    )

    query = ("CREATE TABLE %s (%s)" % (table_name, formatted_column_data))

    try:
        db = __connectToDatabase()
        cursor = db.cursor()
        cursor.execute(query)
        db.close()
    except MySQLdb.Error, e:
        if db:
            db.close()
        __printExceptionQuery(query)
        raise



# insert('test_python_integration', ['name', 'id'], [['test', 3], ['test', 6]])
def insert(table_name, column_names, data):
    # Adds dynamic number of %s for string substitution
    dynamic_sub = ', '.join(['%%s'] * len(column_names))

    # Need to sub column names here to avoid ' from being added
    # (e.g. 'name' instead of name) which won't work with sql
    query = ("Insert Into %s (%s) Values (%s)"
            % (table_name, '%s', dynamic_sub)) % ','.join(column_names)
    if len(column_names) is 1:
        query = ("Insert Into %s (%s) Value ('%s')"
            % (table_name, column_names[0], data[0]))

    db = []
    try:
        db = __connectToDatabase()
        cursor = db.cursor()
        # execute SQL insert query using execute() and commit() methods.
        if len(column_names) is 1:
            cursor.execute(query)
        else:
            cursor.executemany(query, data)
        db.commit()
        db.close()
    except MySQLdb.Error, e:
        if db:
            db.close()
        __printExceptionQuery(query)
        raise



# delete("DELETE FROM test_python_integration")
# delete("DROP TABLE if exists test_python_integration4")
def delete(query):
    db = []
    data = []
    try:
        db = __connectToDatabase()
        cursor = db.cursor()

        # Filtering out warnings here (e.g. if use 'if exists' and
        # table doesn't exist throws warning.
        filterwarnings('ignore', category = MySQLdb.Warning)
        # execute SQL insert query using execute() and commit() methods.
        cursor.execute(query)
        resetwarnings()
        db.commit()
        db.close()
    except MySQLdb.Error, e:
        if db:
            db.close()
        __printExceptionQuery(query)
        raise



# mysql_read('SELECT * FROM test_python_integration')
def read(query):
    db = []
    data = []
    try:
        db = __connectToDatabase()
        cursor = db.cursor()
        cursor.execute(query)
        columns = cursor.description
        data = cursor.fetchall()
        return [{columns[index][0]:column for index, column in
            enumerate(value)} for value in data]
    except MySQLdb.Error, e:
        if db:
            db.close()
        __printExceptionQuery(query)
        raise



def dropPartition(table, p):
    query = ("""ALTER TABLE %s DROP PARTITION p%s""" % (table, p))
    db = []
    try:
        db = __connectToDatabase()
        cursor = db.cursor()
        # execute SQL insert query using execute() and commit() methods.
        cursor.execute(query)
        db.commit()
        db.close()
    except MySQLdb.Error, e:
        if db:
            db.close()
        __printExceptionQuery(query)
        raise



def addPartition(table, p, partition):
    query = ("""ALTER TABLE %s ADD PARTITION (
            PARTITION p%s VALUES IN ((%s)))""" %
            (table, p, partition));
    db = []
    try:
        db = __connectToDatabase()
        cursor = db.cursor()
        # execute SQL insert query using execute() and commit() methods.
        cursor.execute(query)
        db.commit()
        db.close()
        return ''
    except MySQLdb.Error, e:
        if db:
            db.close()
        # Only raise exception if not due to the partition already existing.
        if e.args[0] != _DUPLICATE_PARTITION:
            __printExceptionQuery(query)
            raise
