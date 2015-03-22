CREATE TABLE "languages" (
  "code" char(2) NOT NULL,
  "name" varchar(100) NOT NULL,
  PRIMARY KEY ("code")
);

CREATE TABLE "m" (
  "id" serial4 NOT NULL,
  "group" varchar(100) NOT NULL,
  "file" varchar(100) NOT NULL,
  "checksum" char(32) NOT NULL,
  "executed" timestamp NOT NULL,
  "ready" boolean NOT NULL DEFAULT FALSE,
  PRIMARY KEY ("id"),
  CONSTRAINT "type_file" UNIQUE ("group","file")
);

INSERT INTO "m" ("group", "file", "checksum", "executed", "ready") VALUES
('structures',	'001.sql',	'92cdc3cd1212a5a9bab53bd4bceb7622',	'2015-03-22 07:10:35',	TRUE),
('structures',	'002.sql',	'd5df042cc61262b881f6175f73d91db7',	'2015-03-22 07:10:35',	TRUE),
('basic-data',	'003.sql',	'fdf0cbeb354a7a44956ae62df8013a61',	'2015-03-22 07:10:35',	FALSE);

CREATE TABLE "users" (
  "id" serial4 NOT NULL,
  "name" varchar(100) NOT NULL,
  "password_hash" char(60) NOT NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "name" UNIQUE ("name")
);

