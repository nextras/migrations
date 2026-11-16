CREATE TABLE "languages" (
  "code" char(2) NOT NULL,
  "name" varchar(100) NOT NULL,
  PRIMARY KEY ("code")
);

CREATE TABLE "m" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "group" varchar(100) NOT NULL,
  "file" varchar(100) NOT NULL,
  "checksum" char(32) NOT NULL,
  "executed" datetime NOT NULL,
  "ready" boolean NOT NULL DEFAULT 0,
  CONSTRAINT "type_file" UNIQUE ("group","file")
);

INSERT INTO "m" ("group", "file", "checksum", "executed", "ready") VALUES
('structures',	'001.sql',	'15a2eff586dae5e33a0ca1ed762ac8c5',	'2015-03-22 07:10:35',	1),
('structures',	'002.sql',	'd5df042cc61262b881f6175f73d91db7',	'2015-03-22 07:10:35',	1),
('basic-data',	'003.sql',	'fdf0cbeb354a7a44956ae62df8013a61',	'2015-03-22 07:10:35',	0);

CREATE TABLE "users" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" varchar(100) NOT NULL,
  "password_hash" char(60) NOT NULL,
  CONSTRAINT "name" UNIQUE ("name")
);

