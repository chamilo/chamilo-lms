INSERT INTO roles (name, role) VALUES('Jury president', 'ROLE_JURY_PRESIDENT');
INSERT INTO roles (name, role) VALUES('Jury member', 'ROLE_JURY_MEMBER');
INSERT INTO roles (name, role) VALUES('Jury substitute', 'ROLE_JURY_SUBSTITUTE');
INSERT INTO roles (name, role) VALUES('Director', 'ROLE_DIRECTOR');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES
('log_transactions','exercise_attempt','checkbox','LogTransactions','false','LogTransactionsForExerciseAttempts','LogTransactionsForExerciseAttemptsComment',NULL,'LogTransactionsForExerciseAttemptsText', 1),
('transaction_action_map','exercise_attempt','text','TransactionMapping','a:0:{}','TransactionMapForExerciseAttempts','TransactionMapForExerciseAttemptsComment',NULL,'TransactionMapForExerciseAttemptsText', 1);

CREATE TABLE jury (
  id int NOT NULL AUTO_INCREMENT,
  name varchar(255) DEFAULT NULL,
  branch_id int NULL,
  opening_date datetime DEFAULT NULL,
  closure_date datetime DEFAULT NULL,
  opening_user_id int DEFAULT NULL,
  closure_user_id int DEFAULT NULL,
  exercise_id int NOT NULL,
  PRIMARY KEY(id)
);

CREATE TABLE jury_members (
    id int NOT NULL AUTO_INCREMENT,
    user_id int,
    role_id int,
    jury_id int,
    PRIMARY KEY(id)
);


ALTER TABLE track_e_exercices ADD COLUMN jury_score float(6,2);
ALTER TABLE track_e_exercices ADD COLUMN jury_id INT DEFAULT NULL;

-- Add new configuration setting for action related transaction settings.
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('transaction_action_map','exercise_attempt','text','TransactionMapping','a:0:{}','TransactionMapForExerciseAttempts','TransactionMapForExerciseAttemptsComment',NULL,'TransactionMapForExerciseAttemptsText', 1);


