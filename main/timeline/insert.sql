-- Insert this in your Chamilo 1.9 Database

CREATE TABLE c_timeline (
  id INTEGER  NOT NULL AUTO_INCREMENT,
  c_id INTEGER  NOT NULL,
  headline VARCHAR(255)  NOT NULL,
  type VARCHAR(255)  NOT NULL,
  start_date VARCHAR(255)  NOT NULL,
  end_date VARCHAR(255)  NOT NULL,
  text VARCHAR(255)  NOT NULL,
  media VARCHAR(255)  NOT NULL,
  media_credit VARCHAR(255)  NOT NULL,
  media_caption VARCHAR(255)  NOT NULL,
  title_slide VARCHAR(255)  NOT NULL,
  parent_id INTEGER  DEFAULT 0 NOT NULL,
  status INTEGER  NOT NULL,
  PRIMARY KEY (id, c_id)
);