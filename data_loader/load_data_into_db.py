#!/usr/bin/env python

import traceback
import db_config
from DBConnector import DBConnector


def loadFileIntoDB(db_conn, file_name):
    """
    Parses the data set in the file and loads the data into the appropriate tables in the database
    :param db_conn: Database connection
    :param file_name: Name of file to load
    """
    data_file = open(file_name, 'r')

    for line in data_file.readlines():
        # get the question fields list
        fields = line.split('|')

        # skip the data header
        if fields[0] == 'question':
            continue

        # get the question distractor list
        distractors = fields.pop().split(',')

        # clean whitespaces
        fields = [field.lstrip().rstrip() for field in fields]
        distractors = [distractor.lstrip().rstrip() for distractor in distractors]

        # insert records
        question_id = insertQuestionRecord(fields, db_conn, 'DEF_QUESTION')
        insertDistractorRecords(distractors, question_id, db_conn, 'LKP_QUESTION_DISTRACTOR')


def clearTables(db_conn):
    """
    Truncate the tables before inerting new data
    :param db_conn: Database connection
    """
    truncate_lkp_query = "TRUNCATE LKP_QUESTION_DISTRACTOR"
    negate_keys_query = "SET FOREIGN_KEY_CHECKS = 0"
    truncate_def_query = "TRUNCATE DEF_QUESTION"
    reinstate_keys_query = "SET FOREIGN_KEY_CHECKS = 1"

    db_conn.query(truncate_lkp_query)
    db_conn.query(negate_keys_query)
    db_conn.query(truncate_def_query)
    db_conn.query(reinstate_keys_query)


def insertQuestionRecord(question, db_conn, table):
    """
    Insert a new record into a database
    :param question: List of a question and an answer
    :param db_conn: Database connection
    :param table: Table to insert the record into
    :return: last insert id
    """
    values = "','".join(question)

    query = ("INSERT INTO %s "
             "(question, answer) "
             "VALUES('%s')") % (table, values)

    insert_id = db_conn.query(query)

    return insert_id


def insertDistractorRecords(distractors, question_id, db_conn, table):
    """
    Insert multiple distractors into the LKP table for a single question
    :param distractors: List of distractor strings
    :param question_id: Id of last question inserted
    :param db_conn: Database connection
    :param table: Table to insert the record into
    """
    insert_tuples = [(distractor, question_id) for distractor in distractors]

    query = ("INSERT INTO %s "
             "(distractor, question_id) "
             "VALUES(%%s, %%s)") % table

    db_conn.queryMany(query, insert_tuples)


# MAIN execution
try:
    db_conn = DBConnector(db_config.credentials['host'],
                          db_config.credentials['user'],
                          db_config.credentials['password'],
                          db_config.credentials['database'])

    print 'clearing old data'
    clearTables(db_conn)
    print 'loading new data'
    loadFileIntoDB(db_conn, 'code_challenge_question_dump.csv')
    print 'done loading file into database'

except Exception as e:
    traceback.print_exc()
