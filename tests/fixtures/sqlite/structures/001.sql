CREATE TABLE "users" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" varchar(100) NOT NULL,
  "password_hash" char(60) NOT NULL,
  CONSTRAINT "name" UNIQUE ("name")
);
