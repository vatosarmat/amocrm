BEGIN;

CREATE TABLE access_token (
  id INT UNIQUE NOT NULL PRIMARY KEY AUTO_INCREMENT,
  access_token TEXT NOT NULL,
  refresh_token TEXT NOT NULL,
  expires_in INT NOT NULL,
  base_domain TEXT NOT NULL
);

CREATE TABLE contacts (
  id INT UNIQUE PRIMARY KEY, 
  name TEXT NOT NULL,
  -- {code:{name,values[]}}
  custom_fields JSON NOT NULL
);

CREATE TABLE leads (
  id INT UNIQUE PRIMARY KEY, 
  name TEXT NOT NULL,
  price INT NOT NULL
);

COMMIT;
