# MySQL util functions

import MySQLdb
from warnings import filterwarnings, resetwarnings

USERNAME = "root"
PASSWORD = "baseball"
DATABASE = "BASEBALL"



# create('test_python_integration3', {'name' : 'varchar(20)', 'id' : 'int', 'dou' : 'float'})
def create(table_name, column_data):
    formatted_column_data = ''
    counter = 1
    for name in column_data:
        # For last column, don't add comma after
        if counter == len(column_data):
            formatted_column_data = (formatted_column_data + name + ' ' +
                column_data[name])
        else:
            formatted_column_data = (formatted_column_data + name + ' ' +
                column_data[name] + ', ')
        counter += 1

    query = ("CREATE TABLE %s (%s)" % (table_name, formatted_column_data))

    try:
        db = MySQLdb.connect("localhost", USERNAME, PASSWORD, DATABASE)
        cursor = db.cursor()
        cursor.execute(query)
        db.close()
        return ''
    except MySQLdb.Error, e:
        db.close()
        return {'query' : query, 'error' : e}



# insert('test_python_integration', ['name', 'id'], [['test', 3], ['test', 6]])
def insert(table_name, column_names, data):
    # Adds dynamic number of %s for string substitution
    dynamic_sub = ', '.join(['%%s'] * len(column_names))

    # Need to sub column names here to avoid ' from being added
    # (e.g. 'name' instead of name) which won't work with sql
    query = ("Insert Into test_python_integration (%s) Values (%s)"
            % ('%s', dynamic_sub)) % ','.join(column_names)

    try:
        db = MySQLdb.connect("localhost", USERNAME, PASSWORD, DATABASE)
        cursor = db.cursor()
        # execute SQL insert query using execute() and commit() methods.
        cursor.executemany(query, data)
        db.commit()
        # disconnect from server
        db.close()
        return ''
    except MySQLdb.Error, e:
        db.close()
        return {'query' : query, 'sub_data' : data, 'error' : e}



# delete("DELETE FROM test_python_integration")
# delete("DROP TABLE if exists test_python_integration4")
def delete(query):
    try:
        db = MySQLdb.connect("localhost", USERNAME, PASSWORD, DATABASE)
        cursor = db.cursor()

        # Filtering out warnings here (e.g. if use 'if exists' and
        # table doesn't exist throws warning
        filterwarnings('ignore', category = MySQLdb.Warning)
        # execute SQL insert query using execute() and commit() methods.
        cursor.execute(query)
        resetwarnings()

        db.commit()
        # disconnect from server
        db.close()
        return ''
    except MySQLdb.Error, e:
        db.close()
        return {'query' : query, 'error' : e}



# mysql_read('SELECT * FROM test_python_integration')
def read(query):
    try:
        db = MySQLdb.connect("localhost", USERNAME, PASSWORD, DATABASE)
        cursor = db.cursor()
        cursor.execute(query)
        data = cursor.fetchall()
        return data
    except MySQLdb.Error, e:
        db.close()
        return {'query' : query, 'error' : e}
