-- Extra DB changes needed for new features in work tool

CREATE TABLE IF NOT EXISTS c_student_publication_rel_document (
    id  INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    work_id INT NOT NULL,
    document_id INT NOT NULL,
    c_id INT NOT NULL
);

CREATE TABLE IF NOT EXISTS c_student_publication_rel_user (
    id  INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    work_id INT NOT NULL,
    user_id INT NOT NULL,
    c_id INT NOT NULL
);

CREATE TABLE IF NOT EXISTS c_student_publication_comment (
  id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  work_id INT NOT NULL,
  c_id INT NOT NULL,
  comment text,
  user_id int NOT NULL,
  sent_at datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE c_student_publication ADD COLUMN document_id int DEFAULT 0;

-- Update configuration.php:
-- $_configuration['add_document_to_work'] = true;
